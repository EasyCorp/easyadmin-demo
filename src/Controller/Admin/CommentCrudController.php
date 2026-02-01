<?php

namespace App\Controller\Admin;

use App\Entity\Comment;
use App\Enum\CommentStatus;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
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
use Symfony\Component\HttpFoundation\Response;

class CommentCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Comment::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('comment.label')
            ->setEntityLabelInPlural('comment.label_plural')
            ->setDefaultSort(['publishedAt' => 'DESC'])
            ->setSearchFields(['content', 'author.fullName', 'post.title'])
            ->setDefaultRowAction(Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->onlyOnIndex();

        yield TextField::new('content')
            ->setLabel('comment.content')
            ->setTemplatePath('admin/comment/_preview.html.twig')
            ->onlyOnIndex();

        yield TextareaField::new('content')
            ->setLabel('comment.content')
            ->setNumOfRows(5)
            ->onlyOnForms();

        yield AssociationField::new('author')
            ->hideOnIndex()
            ->setLabel('comment.author')
            ->setSortProperty('fullName');

        yield AssociationField::new('post')
            ->setLabel('comment.post')
            ->setSortProperty('title');

        yield ChoiceField::new('status')
            ->setLabel('comment.status')
            ->setChoices(CommentStatus::choices())
            ->renderAsBadges(CommentStatus::badges())
            ->setPreferredChoices([CommentStatus::Approved]);

        yield DateTimeField::new('publishedAt')
            ->setLabel('comment.publishedAt')
            ->setFormat('medium')
            ->onlyOnIndex();

        yield DateTimeField::new('publishedAt')
            ->setLabel('comment.publishedAt')
            ->hideOnIndex();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('author', 'comment.author'))
            ->add(EntityFilter::new('post', 'comment.post'))
            ->add(ChoiceFilter::new('status', 'comment.status')->setTranslatableChoices(CommentStatus::filterChoices()))
            ->add(DateTimeFilter::new('publishedAt', 'comment.publishedAt'));
    }

    public function configureActions(Actions $actions): Actions
    {
        // Permissions: Only ADMIN can delete comments
        $actions
            ->setPermission(Action::DELETE, 'ROLE_ADMIN');

        // Row actions
        $approveAction = Action::new('approve', 'action.approve', 'fa fa-check')
            ->linkToCrudAction('approveComment')
            ->displayIf(static fn (Comment $comment): bool => $comment->isPending())
            ->asSuccessAction();

        $rejectAction = Action::new('reject', 'action.reject', 'fa fa-times')
            ->linkToCrudAction('rejectComment')
            ->displayIf(static fn (Comment $comment): bool => $comment->isPending())
            ->asDefaultAction();

        $markSpamAction = Action::new('markSpam', 'action.mark_spam', 'fa fa-ban')
            ->linkToCrudAction('markCommentAsSpam')
            ->displayIf(static fn (Comment $comment): bool => !$comment->isSpam())
            ->asDangerAction()
            ->askConfirmation('comment.confirm.mark_spam');

        // Batch actions
        $batchApprove = Action::new('batchApprove', 'batch.approve_selected', 'fa fa-check')
            ->linkToCrudAction('batchApprove')
            ->asSuccessAction()
            ->createAsBatchAction();

        $batchReject = Action::new('batchReject', 'batch.reject_selected', 'fa fa-times')
            ->linkToCrudAction('batchReject')
            ->asDefaultAction()
            ->createAsBatchAction();

        $batchSpam = Action::new('batchSpam', 'batch.mark_as_spam', 'fa fa-ban')
            ->linkToCrudAction('batchMarkAsSpam')
            ->asDangerAction()
            ->createAsBatchAction()
            ->askConfirmation('comment.confirm.batch_spam');

        return $actions
            // Row actions
            ->add(Crud::PAGE_INDEX, $markSpamAction)
            ->add(Crud::PAGE_INDEX, $rejectAction)
            ->add(Crud::PAGE_INDEX, $approveAction)
            ->add(Crud::PAGE_DETAIL, $markSpamAction)
            ->add(Crud::PAGE_DETAIL, $rejectAction)
            ->add(Crud::PAGE_DETAIL, $approveAction)
            // Batch actions
            ->add(Crud::PAGE_INDEX, $batchApprove)
            ->add(Crud::PAGE_INDEX, $batchReject)
            ->add(Crud::PAGE_INDEX, $batchSpam);
    }

    public function approveComment(AdminContext $context): Response
    {
        /** @var Comment $comment */
        $comment = $context->getEntity()->getInstance();
        $comment->approve();

        $this->entityManager->flush();

        $this->addFlash('success', 'comment.flash.approved');

        return $this->redirectToRoute('admin_comment_index');
    }

    public function rejectComment(AdminContext $context): Response
    {
        /** @var Comment $comment */
        $comment = $context->getEntity()->getInstance();
        $comment->reject();

        $this->entityManager->flush();

        $this->addFlash('success', 'comment.flash.rejected');

        return $this->redirectToRoute('admin_comment_index');
    }

    public function markCommentAsSpam(AdminContext $context): Response
    {
        /** @var Comment $comment */
        $comment = $context->getEntity()->getInstance();
        $comment->markAsSpam();

        $this->entityManager->flush();

        $this->addFlash('success', 'comment.flash.marked_spam');

        return $this->redirectToRoute('admin_comment_index');
    }

    public function batchApprove(BatchActionDto $batchActionDto): Response
    {
        $repository = $this->entityManager->getRepository(Comment::class);
        $count = 0;

        foreach ($batchActionDto->getEntityIds() as $id) {
            /** @var Comment|null $comment */
            $comment = $repository->find($id);
            if ($comment && $comment->isPending()) {
                $comment->approve();
                ++$count;
            }
        }

        $this->entityManager->flush();

        $this->addFlash('success', sprintf('%d comment(s) approved.', $count));

        return $this->redirect($batchActionDto->getReferrerUrl());
    }

    public function batchReject(BatchActionDto $batchActionDto): Response
    {
        $repository = $this->entityManager->getRepository(Comment::class);
        $count = 0;

        foreach ($batchActionDto->getEntityIds() as $id) {
            /** @var Comment|null $comment */
            $comment = $repository->find($id);
            if ($comment && $comment->isPending()) {
                $comment->reject();
                ++$count;
            }
        }

        $this->entityManager->flush();

        $this->addFlash('success', sprintf('%d comment(s) rejected.', $count));

        return $this->redirect($batchActionDto->getReferrerUrl());
    }

    public function batchMarkAsSpam(BatchActionDto $batchActionDto): Response
    {
        $repository = $this->entityManager->getRepository(Comment::class);
        $count = 0;

        foreach ($batchActionDto->getEntityIds() as $id) {
            /** @var Comment|null $comment */
            $comment = $repository->find($id);
            if ($comment && !$comment->isSpam()) {
                $comment->markAsSpam();
                ++$count;
            }
        }

        $this->entityManager->flush();

        $this->addFlash('success', sprintf('%d comment(s) marked as spam.', $count));

        return $this->redirect($batchActionDto->getReferrerUrl());
    }
}
