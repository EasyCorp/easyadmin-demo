<?php

namespace App\Controller\Admin;

use App\Admin\Action\SourceCodeAction;
use App\Admin\EntityPageTitle;
use App\Admin\Field\RatingField;
use App\Entity\Review;
use App\Enum\ReviewStatus;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\TranslatableMessage;
use function Symfony\Component\Translation\t;

class ReviewCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Review::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('review.label')
            ->setPageTitle(Crud::PAGE_DETAIL, static fn (Review $review): TranslatableMessage => EntityPageTitle::detail('review', ['customer' => (string) $review->getCustomer()]))
            ->setPageTitle(Crud::PAGE_EDIT, static fn (Review $review): TranslatableMessage => EntityPageTitle::edit('review', ['customer' => (string) $review->getCustomer()]))
            ->setEntityLabelInPlural('review.label_plural')
            ->setDefaultSort(['publishedAt' => 'DESC'])
            ->setSearchFields(['content', 'customer.name', 'product.name'])
            ->setDefaultRowAction(Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->onlyOnIndex();

        yield TextField::new('content')
            ->setLabel('review.content')
            ->setTemplatePath('admin/review/_preview.html.twig')
            ->onlyOnIndex();

        yield TextareaField::new('content')
            ->setLabel('review.content')
            ->setNumOfRows(5)
            ->hideOnIndex();

        yield AssociationField::new('customer')
            ->hideOnIndex()
            ->setLabel('review.customer')
            ->setSortProperty('name')
            ->autocomplete();

        // a regular dropdown instead of autocomplete() because the autocomplete endpoint
        // of PlantCrudController only returns plants and supplies can be reviewed too
        yield AssociationField::new('product')
            ->setLabel('review.product')
            ->setCrudController(PlantCrudController::class)
            ->setTemplatePath('admin/review/_product.html.twig')
            ->setSortProperty('name');

        yield RatingField::new('rating', 'review.rating');

        yield ChoiceField::new('status')
            ->setLabel('review.status')
            ->renderAsBadges(ReviewStatus::badges())
            ->setPreferredChoices([ReviewStatus::Approved]);

        yield DateTimeField::new('publishedAt')
            ->setLabel('review.publishedAt')
            ->setFormat('medium')
            ->onlyOnIndex();

        yield DateTimeField::new('publishedAt')
            ->setLabel('review.publishedAt')
            ->hideOnIndex();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('customer', 'review.customer'))
            ->add(EntityFilter::new('product', 'review.product'))
            ->add(ChoiceFilter::new('status', 'review.status')->setTranslatableChoices(ReviewStatus::cases()))
            ->add(NumericFilter::new('rating', 'review.rating'))
            ->add(DateTimeFilter::new('publishedAt', 'review.publishedAt'));
    }

    public function configureActions(Actions $actions): Actions
    {
        // custom actions must declare their permissions too because they don't inherit the ones of the
        // dashboard. They hide the buttons and also protect the URLs of the actions, so their methods don't check them again
        $actions
            ->setPermission('approve', 'ROLE_EDITOR')
            ->setPermission('reject', 'ROLE_EDITOR')
            ->setPermission('markSpam', 'ROLE_EDITOR')
            ->setPermission('batchApprove', 'ROLE_EDITOR')
            ->setPermission('batchReject', 'ROLE_EDITOR')
            ->setPermission('batchSpam', 'ROLE_EDITOR');
        $approveAction = Action::new('approve', 'action.approve', 'check')
            ->linkToCrudAction('approveReview')
            ->renderAsForm()
            ->displayIf(static fn (Review $review): bool => $review->isPending())
            ->asSuccessAction();

        $rejectAction = Action::new('reject', 'action.reject', 'x')
            ->linkToCrudAction('rejectReview')
            ->renderAsForm()
            ->displayIf(static fn (Review $review): bool => $review->isPending())
            ->asDefaultAction();

        $markSpamAction = Action::new('markSpam', 'action.mark_spam', 'ban')
            ->linkToCrudAction('markReviewAsSpam')
            ->renderAsForm()
            ->displayIf(static fn (Review $review): bool => !$review->isSpam())
            ->asDangerAction()
            ->askConfirmation('review.confirm.mark_spam');
        $batchApprove = Action::new('batchApprove', 'batch.approve_selected', 'check')
            ->linkToCrudAction('batchApprove')
            ->asSuccessAction()
            ->createAsBatchAction();

        $batchReject = Action::new('batchReject', 'batch.reject_selected', 'x')
            ->linkToCrudAction('batchReject')
            ->asDefaultAction()
            ->createAsBatchAction();

        $batchSpam = Action::new('batchSpam', 'batch.mark_as_spam', 'ban')
            ->linkToCrudAction('batchMarkAsSpam')
            ->asDangerAction()
            ->createAsBatchAction()
            ->askConfirmation('review.confirm.batch_spam');

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            // static::class because the moderation queue must link to the source code of its own controller
            ->add(Crud::PAGE_INDEX, SourceCodeAction::create())
            ->add(Crud::PAGE_INDEX, $markSpamAction)
            ->add(Crud::PAGE_INDEX, $rejectAction)
            ->add(Crud::PAGE_INDEX, $approveAction)
            ->add(Crud::PAGE_DETAIL, $markSpamAction)
            ->add(Crud::PAGE_DETAIL, $rejectAction)
            ->add(Crud::PAGE_DETAIL, $approveAction)
            ->add(Crud::PAGE_INDEX, $batchApprove)
            ->add(Crud::PAGE_INDEX, $batchReject)
            ->add(Crud::PAGE_INDEX, $batchSpam);
    }

    #[AdminRoute('/{entityId:review.id}/approve', options: ['methods' => ['POST']])]
    public function approveReview(Review $review): Response
    {
        $review->approve();

        $this->entityManager->flush();

        $this->addFlash('success', 'review.flash.approved');

        return $this->redirectToRoute($this->getIndexRouteName());
    }

    #[AdminRoute('/{entityId:review.id}/reject', options: ['methods' => ['POST']])]
    public function rejectReview(Review $review): Response
    {
        $review->reject();

        $this->entityManager->flush();

        $this->addFlash('success', 'review.flash.rejected');

        return $this->redirectToRoute($this->getIndexRouteName());
    }

    #[AdminRoute('/{entityId:review.id}/mark-as-spam', options: ['methods' => ['POST']])]
    public function markReviewAsSpam(Review $review): Response
    {
        $review->markAsSpam();

        $this->entityManager->flush();

        $this->addFlash('success', 'review.flash.marked_spam');

        return $this->redirectToRoute($this->getIndexRouteName());
    }

    #[AdminRoute(options: ['methods' => ['POST']])]
    public function batchApprove(BatchActionDto $batchActionDto): Response
    {
        $count = $this->processBatchAction(
            $batchActionDto,
            static fn (Review $review): bool => $review->isPending(),
            static fn (Review $review) => $review->approve()
        );

        $this->addFlash('success', t('review.flash.batch_approved', ['count' => $count]));

        return $this->redirectToRoute($this->getIndexRouteName());
    }

    #[AdminRoute(options: ['methods' => ['POST']])]
    public function batchReject(BatchActionDto $batchActionDto): Response
    {
        $count = $this->processBatchAction(
            $batchActionDto,
            static fn (Review $review): bool => $review->isPending(),
            static fn (Review $review) => $review->reject()
        );

        $this->addFlash('success', t('review.flash.batch_rejected', ['count' => $count]));

        return $this->redirectToRoute($this->getIndexRouteName());
    }

    #[AdminRoute(options: ['methods' => ['POST']])]
    public function batchMarkAsSpam(BatchActionDto $batchActionDto): Response
    {
        $count = $this->processBatchAction(
            $batchActionDto,
            static fn (Review $review): bool => !$review->isSpam(),
            static fn (Review $review) => $review->markAsSpam()
        );

        $this->addFlash('success', t('review.flash.batch_marked_spam', ['count' => $count]));

        return $this->redirectToRoute($this->getIndexRouteName());
    }

    protected function getIndexRouteName(): string
    {
        return 'admin_review_index';
    }

    private function processBatchAction(BatchActionDto $batchActionDto, callable $isApplicable, callable $apply): int
    {
        // the entity class comes from this controller because the one in the DTO is sent by the browser
        if (!$this->isCsrfTokenValid('ea-batch-action-'.$batchActionDto->getName().'-'.Review::class, $batchActionDto->getCsrfToken())) {
            throw $this->createAccessDeniedException();
        }

        $repository = $this->entityManager->getRepository(Review::class);
        $count = 0;

        foreach ($batchActionDto->getEntityIds() as $id) {
            $review = $repository->find($id);
            if (null !== $review && $isApplicable($review)) {
                $apply($review);
                ++$count;
            }
        }

        $this->entityManager->flush();

        return $count;
    }
}
