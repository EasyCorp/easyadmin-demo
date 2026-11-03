<?php

namespace App\Controller\Admin;

use App\Admin\Action\SourceCodeAction;
use App\Admin\EntityPageTitle;
use App\Entity\Customer;
use App\Enum\CustomerSource;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CountryField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\LanguageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\LocaleField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimezoneField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\CountryFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\LanguageFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TimezoneFilter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints\Image;
use function Symfony\Component\Translation\t;

class CustomerCrudController extends AbstractCrudController
{
    // the database columns of these properties are too short for some of the values of the full lists
    private const LANGUAGES = ['da', 'de', 'en', 'es', 'fi', 'fr', 'it', 'nl', 'pt', 'sv'];
    private const LOCALES = ['da_DK', 'de_DE', 'en_AU', 'en_CA', 'en_GB', 'en_IN', 'en_NG', 'en_US', 'es_AR', 'es_CO', 'es_ES', 'es_MX', 'es_VE', 'fi_FI', 'fr_FR', 'it_IT', 'nl_NL', 'pt_BR', 'sv_SE'];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Customer::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('customer.label')
            ->setPageTitle(Crud::PAGE_DETAIL, static fn (Customer $customer): TranslatableMessage => EntityPageTitle::detail('customer', ['name' => (string) $customer->getName()]))
            ->setPageTitle(Crud::PAGE_EDIT, static fn (Customer $customer): TranslatableMessage => EntityPageTitle::edit('customer', ['name' => (string) $customer->getName()]))
            ->setEntityLabelInPlural('customer.label_plural')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setSearchFields(['name', 'email', 'phone'])
            ->hideNullValues();
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->onlyOnIndex();

        yield TextField::new('name')
            ->setLabel('customer.name')
            ->setTemplatePath('admin/customer/_status.html.twig')
            ->onlyOnIndex();

        yield FormField::addTab('customer.tab.details', 'contact');

        yield TextField::new('name')
            ->setLabel('customer.name')
            ->hideOnIndex();

        yield ImageField::new('avatar')
            ->setLabel('customer.avatar')
            ->setUploadDir('public/uploads/avatars')
            ->setBasePath('uploads/avatars')
            ->setUploadedFileNamePattern('[slug]-[contenthash].[extension]')
            ->setFileConstraints(new Image(maxSize: '2M', mimeTypes: ['image/jpeg', 'image/png', 'image/webp']))
            ->hideOnIndex();

        yield EmailField::new('email')
            ->setLabel('customer.email');

        yield TelephoneField::new('phone')
            ->setLabel('customer.phone')
            ->hideOnIndex();

        yield ChoiceField::new('source')
            ->setLabel('customer.source')
            ->renderAsBadges(CustomerSource::badges())
            ->setPreferredChoices([CustomerSource::Website]);

        yield BooleanField::new('isConfirmed')
            ->setLabel('customer.isConfirmed')
            ->renderAsSwitch(false)
            ->hideOnIndex();

        yield BooleanField::new('acceptsNewsletter')
            ->setLabel('customer.acceptsNewsletter')
            ->renderAsSwitch(false)
            ->hideOnIndex();

        yield DateTimeField::new('createdAt')
            ->setLabel('customer.createdAt')
            ->onlyOnDetail();

        yield FormField::addTab('customer.tab.address', 'map');

        yield AssociationField::new('address')
            ->setLabel('customer.address')
            ->renderAsEmbeddedForm(AddressCrudController::class)
            ->onlyOnForms();

        // the embedded form doesn't configure the value displayed on the detail page
        yield TextField::new('address')
            ->setLabel('customer.address')
            ->setTemplatePath('admin/customer/_address.html.twig')
            ->onlyOnDetail();

        yield CountryField::new('country')
            ->setLabel('customer.country');

        yield LanguageField::new('language')
            ->setLabel('customer.language')
            ->includeOnly(self::LANGUAGES)
            ->hideOnIndex();

        yield LocaleField::new('locale')
            ->setLabel('customer.locale')
            ->includeOnly(self::LOCALES)
            ->hideOnIndex();

        yield TimezoneField::new('timezone')
            ->setLabel('customer.timezone')
            ->hideOnIndex();

        yield FormField::addTab('customer.tab.activity', 'shopping-cart')
            ->onlyOnDetail();

        yield CollectionField::new('orders')
            ->setLabel('customer.orders')
            ->setTemplatePath('admin/customer/_orders.html.twig')
            ->onlyOnDetail();

        yield AssociationField::new('reviews')
            ->setLabel('customer.reviews')
            ->setCrudController(ReviewCrudController::class)
            ->onlyOnDetail();

        yield FormField::addTab('customer.tab.internal', 'file-text')
            ->setPermission('ROLE_ADMIN');

        yield TextareaField::new('notes')
            ->setPermission('ROLE_ADMIN')
            ->setLabel('customer.notes')
            ->setNumOfRows(3)
            ->hideOnIndex();

        yield TextField::new('ipAddress')
            ->setPermission('ROLE_ADMIN')
            ->setLabel('customer.ipAddress')
            ->onlyOnDetail();

        yield DateTimeField::new('createdAt')
            ->setLabel('customer.createdAt')
            ->onlyOnIndex();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('email', 'customer.email'))
            ->add(BooleanFilter::new('isConfirmed', 'customer.isConfirmed'))
            ->add(BooleanFilter::new('acceptsNewsletter', 'customer.acceptsNewsletter'))
            ->add(ChoiceFilter::new('source', 'customer.source')->setTranslatableChoices(CustomerSource::cases()))
            ->add(LanguageFilter::new('language', 'customer.language'))
            ->add(CountryFilter::new('country', 'customer.country'))
            ->add(TimezoneFilter::new('timezone', 'customer.timezone')->preferredChoices(['Europe/Madrid', 'Europe/Paris', 'Europe/Berlin']))
            ->add(DateTimeFilter::new('createdAt', 'customer.createdAt'));
    }

    public function configureActions(Actions $actions): Actions
    {
        // custom actions must declare their permissions too because they don't inherit the ones of the
        // dashboard. They hide the buttons and also protect the URLs of the actions, so their methods don't check them again
        $actions
            ->setPermission('confirm', 'ROLE_EDITOR')
            ->setPermission('unsubscribe', 'ROLE_EDITOR')
            ->setPermission('batchConfirm', 'ROLE_EDITOR')
            ->setPermission('batchUnsubscribe', 'ROLE_EDITOR');
        $confirmAction = Action::new('confirm', 'action.confirm', 'circle-check')
            ->linkToCrudAction('confirmCustomer')
            ->renderAsForm()
            ->displayIf(static fn (Customer $customer): bool => !$customer->isConfirmed())
            ->asSuccessAction();

        $unsubscribeAction = Action::new('unsubscribe', 'action.unsubscribe', 'mail-x')
            ->linkToCrudAction('unsubscribeCustomer')
            ->renderAsForm()
            ->displayIf(static fn (Customer $customer): bool => $customer->isAcceptsNewsletter())
            ->asWarningAction()
            ->askConfirmation('customer.confirm.unsubscribe');
        $batchConfirm = Action::new('batchConfirm', 'batch.confirm_selected', 'circle-check')
            ->linkToCrudAction('batchConfirm')
            ->asSuccessAction()
            ->createAsBatchAction();

        $batchUnsubscribe = Action::new('batchUnsubscribe', 'batch.unsubscribe_selected', 'mail-x')
            ->linkToCrudAction('batchUnsubscribe')
            ->asWarningAction()
            ->createAsBatchAction()
            ->askConfirmation('customer.confirm.batch_unsubscribe');

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, SourceCodeAction::create())
            ->add(Crud::PAGE_INDEX, $unsubscribeAction)
            ->add(Crud::PAGE_INDEX, $confirmAction)
            ->add(Crud::PAGE_DETAIL, $unsubscribeAction)
            ->add(Crud::PAGE_DETAIL, $confirmAction)
            ->add(Crud::PAGE_INDEX, $batchConfirm)
            ->add(Crud::PAGE_INDEX, $batchUnsubscribe);
    }

    public function deleteEntity(EntityManagerInterface $entityManager, object $entityInstance): void
    {
        // orders must be kept for accounting, and the foreign key would make the delete fail anyway
        if ($entityInstance instanceof Customer && !$entityInstance->getOrders()->isEmpty()) {
            $this->addFlash('danger', 'customer.flash.has_orders');

            return;
        }

        parent::deleteEntity($entityManager, $entityInstance);
    }

    #[AdminRoute('/{entityId:customer.id}/confirm', options: ['methods' => ['POST']])]
    public function confirmCustomer(Customer $customer): Response
    {
        $customer->confirm();

        $this->entityManager->flush();

        $this->addFlash('success', 'customer.flash.confirmed');

        return $this->redirectToRoute('admin_customer_index');
    }

    #[AdminRoute('/{entityId:customer.id}/unsubscribe', options: ['methods' => ['POST']])]
    public function unsubscribeCustomer(Customer $customer): Response
    {
        $customer->unsubscribeFromNewsletter();

        $this->entityManager->flush();

        $this->addFlash('success', 'customer.flash.unsubscribed');

        return $this->redirectToRoute('admin_customer_index');
    }

    #[AdminRoute(options: ['methods' => ['POST']])]
    public function batchConfirm(BatchActionDto $batchActionDto): Response
    {
        $count = $this->processBatchAction(
            $batchActionDto,
            static fn (Customer $customer): bool => !$customer->isConfirmed(),
            static fn (Customer $customer) => $customer->confirm()
        );

        $this->addFlash('success', t('customer.flash.batch_confirmed', ['count' => $count]));

        return $this->redirectToRoute('admin_customer_index');
    }

    #[AdminRoute(options: ['methods' => ['POST']])]
    public function batchUnsubscribe(BatchActionDto $batchActionDto): Response
    {
        $count = $this->processBatchAction(
            $batchActionDto,
            static fn (Customer $customer): bool => $customer->isAcceptsNewsletter(),
            static fn (Customer $customer) => $customer->unsubscribeFromNewsletter()
        );

        $this->addFlash('success', t('customer.flash.batch_unsubscribed', ['count' => $count]));

        return $this->redirectToRoute('admin_customer_index');
    }

    private function processBatchAction(BatchActionDto $batchActionDto, callable $isApplicable, callable $apply): int
    {
        // the entity class comes from this controller because the one in the DTO is sent by the browser
        if (!$this->isCsrfTokenValid('ea-batch-action-'.$batchActionDto->getName().'-'.Customer::class, $batchActionDto->getCsrfToken())) {
            throw $this->createAccessDeniedException();
        }

        $repository = $this->entityManager->getRepository(Customer::class);
        $count = 0;

        foreach ($batchActionDto->getEntityIds() as $id) {
            $customer = $repository->find($id);
            if (null !== $customer && $isApplicable($customer)) {
                $apply($customer);
                ++$count;
            }
        }

        $this->entityManager->flush();

        return $count;
    }
}
