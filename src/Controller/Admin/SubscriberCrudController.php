<?php

namespace App\Controller\Admin;

use App\Entity\Subscriber;
use App\Enum\SubscriberSource;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\HttpFoundation\Response;

class SubscriberCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Subscriber::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('subscriber.label')
            ->setEntityLabelInPlural('subscriber.label_plural')
            ->setDefaultSort(['subscribedAt' => 'DESC'])
            ->setSearchFields(['email', 'name'])
            ->hideNullValues();
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->onlyOnIndex();

        yield TextField::new('email')
            ->setLabel('subscriber.email')
            ->setTemplatePath('admin/subscriber/_status.html.twig')
            ->onlyOnIndex();

        yield EmailField::new('email')
            ->setLabel('subscriber.email')
            ->hideOnIndex();

        yield TextField::new('name')
            ->setLabel('subscriber.name');

        yield BooleanField::new('isConfirmed')
            ->setLabel('subscriber.isConfirmed')
            ->renderAsSwitch(false);

        yield ChoiceField::new('source')
            ->setLabel('subscriber.source')
            ->setChoices(SubscriberSource::choices())
            ->renderAsBadges(SubscriberSource::badges());

        yield ChoiceField::new('locale')
            ->setLabel('subscriber.locale')
            ->setChoices([
                'English' => 'en',
                'Spanish' => 'es',
            ])
            ->onlyOnForms();

        yield DateTimeField::new('subscribedAt')
            ->setLabel('subscriber.subscribedAt')
            ->hideOnForm();

        yield DateTimeField::new('confirmedAt')
            ->setLabel('subscriber.confirmedAt')
            ->onlyOnDetail();

        yield DateTimeField::new('unsubscribedAt')
            ->setLabel('subscriber.unsubscribedAt')
            ->onlyOnDetail();

        yield TextareaField::new('notes')
            ->setLabel('subscriber.notes')
            ->setNumOfRows(3)
            ->onlyOnForms();

        yield TextField::new('ipAddress')
            ->setLabel('subscriber.ipAddress')
            ->onlyOnDetail();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('email', 'subscriber.email'))
            ->add(BooleanFilter::new('isConfirmed', 'subscriber.isConfirmed'))
            ->add(ChoiceFilter::new('source', 'subscriber.source')->setTranslatableChoices(SubscriberSource::filterChoices()))
            ->add(ChoiceFilter::new('locale', 'subscriber.locale')->setChoices([
                'English' => 'en',
                'Spanish' => 'es',
            ]))
            ->add(DateTimeFilter::new('subscribedAt', 'subscriber.subscribedAt'));
    }

    public function configureActions(Actions $actions): Actions
    {
        // Row actions
        $confirmAction = Action::new('confirm', 'action.confirm', 'fa fa-check-circle')
            ->linkToCrudAction('confirmSubscriber')
            ->displayIf(static fn (Subscriber $subscriber): bool => !$subscriber->isConfirmed())
            ->asSuccessAction();

        $unsubscribeAction = Action::new('unsubscribe', 'action.unsubscribe', 'fa fa-user-minus')
            ->linkToCrudAction('unsubscribeSubscriber')
            ->displayIf(static fn (Subscriber $subscriber): bool => $subscriber->isActive())
            ->asWarningAction();

        // Batch actions
        $batchConfirm = Action::new('batchConfirm', 'batch.confirm_selected', 'fa fa-check-circle')
            ->linkToCrudAction('batchConfirm')
            ->asSuccessAction()
            ->createAsBatchAction();

        $batchUnsubscribe = Action::new('batchUnsubscribe', 'batch.unsubscribe_selected', 'fa fa-user-minus')
            ->linkToCrudAction('batchUnsubscribe')
            ->asWarningAction()
            ->createAsBatchAction();

        return $actions
            // Row actions
            ->add(Crud::PAGE_INDEX, $unsubscribeAction)
            ->add(Crud::PAGE_INDEX, $confirmAction)
            ->add(Crud::PAGE_DETAIL, $unsubscribeAction)
            ->add(Crud::PAGE_DETAIL, $confirmAction)
            // Batch actions
            ->add(Crud::PAGE_INDEX, $batchConfirm)
            ->add(Crud::PAGE_INDEX, $batchUnsubscribe);
    }

    public function confirmSubscriber(AdminContext $context): Response
    {
        /** @var Subscriber $subscriber */
        $subscriber = $context->getEntity()->getInstance();
        $subscriber->confirm();

        $this->entityManager->flush();

        $this->addFlash('success', 'subscriber.flash.confirmed');

        return $this->redirectToRoute('admin_subscriber_index');
    }

    public function unsubscribeSubscriber(AdminContext $context): Response
    {
        /** @var Subscriber $subscriber */
        $subscriber = $context->getEntity()->getInstance();
        $subscriber->unsubscribe();

        $this->entityManager->flush();

        $this->addFlash('success', 'subscriber.flash.unsubscribed');

        return $this->redirectToRoute('admin_subscriber_index');
    }

    public function batchConfirm(BatchActionDto $batchActionDto): Response
    {
        $repository = $this->entityManager->getRepository(Subscriber::class);
        $count = 0;

        foreach ($batchActionDto->getEntityIds() as $id) {
            /** @var Subscriber|null $subscriber */
            $subscriber = $repository->find($id);
            if ($subscriber && !$subscriber->isConfirmed()) {
                $subscriber->confirm();
                ++$count;
            }
        }

        $this->entityManager->flush();

        $this->addFlash('success', sprintf('%d subscriber(s) confirmed.', $count));

        return $this->redirect($batchActionDto->getReferrerUrl());
    }

    public function batchUnsubscribe(BatchActionDto $batchActionDto): Response
    {
        $repository = $this->entityManager->getRepository(Subscriber::class);
        $count = 0;

        foreach ($batchActionDto->getEntityIds() as $id) {
            /** @var Subscriber|null $subscriber */
            $subscriber = $repository->find($id);
            if ($subscriber && $subscriber->isActive()) {
                $subscriber->unsubscribe();
                ++$count;
            }
        }

        $this->entityManager->flush();

        $this->addFlash('success', sprintf('%d subscriber(s) unsubscribed.', $count));

        return $this->redirect($batchActionDto->getReferrerUrl());
    }
}
