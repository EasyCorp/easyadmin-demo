<?php

namespace App\Controller\Admin;

use App\Admin\Action\SourceCodeAction;
use App\Admin\Filter\SupplierWithMinProductsFilter;
use App\Entity\OrderLine;
use App\Entity\Product;
use App\Entity\Tag;
use App\Enum\ProductStatus;
use App\Security\ProductVoter;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\ActionGroup;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\TextAlign;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FileField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\PercentField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\BadgeVariant;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Validator\Constraints\Range;

/**
 * Plants and supplies are the same entity, so they share everything except the
 * family they belong to and the botanical fields. Each child class generates its
 * own routes, including the ones of the custom actions defined here.
 */
abstract class AbstractProductCrudController extends AbstractCrudController
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    abstract protected function managesPlants(): bool;

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setSearchFields(['name', 'description', 'botanicalName', 'family.name', 'supplier.name'])
            ->setEntityPermission(ProductVoter::VIEW);
    }

    /**
     * The entity permission protects the detail page and hides the products that the
     * user can't see. This query removes them from the listing, so the pagination
     * of anonymous visitors and readers only counts the products they can see.
     */
    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        if (!$this->isGranted('ROLE_EDITOR')) {
            $queryBuilder
                ->andWhere('entity.status = :published')
                ->setParameter('published', ProductStatus::Published);
        }

        // the alias can't be 'family' because EasyAdmin uses it when sorting or searching by family
        return $queryBuilder
            ->innerJoin('entity.family', 'product_family')
            ->andWhere('product_family.isPlantFamily = :isPlantFamily')
            ->setParameter('isPlantFamily', $this->managesPlants());
    }

    public function configureFields(string $pageName): iterable
    {
        if (Crud::PAGE_INDEX === $pageName) {
            return $this->getIndexFields();
        }

        return $this->getDetailAndFormFields();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('name', 'product.name'))
            ->add(EntityFilter::new('family', 'product.family')
                ->setFormTypeOption('value_type_options.query_builder', fn (EntityRepository $repository): QueryBuilder => $this->restrictToManagedFamilies($repository->createQueryBuilder('entity'))))
            ->add(EntityFilter::new('tags', 'product.tags'))
            ->add(EntityFilter::new('supplier', 'product.supplier'))
            ->add(SupplierWithMinProductsFilter::new('supplierMinProducts', 'filter.supplier_min_products'))
            ->add(EntityFilter::new('manager', 'product.manager')
                ->setFormTypeOption('value_type_options.query_builder', fn (EntityRepository $repository): QueryBuilder => $this->restrictToStaff($repository->createQueryBuilder('entity'))))
            ->add(ChoiceFilter::new('status', 'product.status')->renderExpanded()->setTranslatableChoices(ProductStatus::cases()))
            ->add(NumericFilter::new('stock', 'product.stock'))
            ->add(BooleanFilter::new('isFeatured', 'product.isFeatured'))
            ->add(DateTimeFilter::new('createdAt', 'product.createdAt'));
    }

    public function configureActions(Actions $actions): Actions
    {
        // editors can only change the products they manage (see ProductVoter). Permissions hide the
        // buttons and also protect the URLs of the actions, so their methods don't check them again
        $actions
            ->setPermission(Action::EDIT, ProductVoter::EDIT)
            ->setPermission('publish', ProductVoter::EDIT)
            ->setPermission('unpublish', ProductVoter::EDIT)
            ->setPermission('archive', ProductVoter::EDIT)
            ->setPermission('batchPublish', 'ROLE_EDITOR')
            ->setPermission('batchArchive', 'ROLE_EDITOR')
            ->setPermission('batchFeatured', 'ROLE_EDITOR');

        $publishAction = Action::new('publish', 'action.publish', 'circle-check')
            ->linkToCrudAction('publishProduct')
            ->renderAsForm()
            ->displayIf(static fn (Product $product): bool => $product->isDraft())
            ->asSuccessAction();

        $unpublishAction = Action::new('unpublish', 'action.unpublish', 'eye-off')
            ->linkToCrudAction('unpublishProduct')
            ->renderAsForm()
            ->displayIf(static fn (Product $product): bool => $product->isPublished())
            ->asDangerAction();

        $archiveAction = Action::new('archive', 'action.archive', 'archive')
            ->linkToCrudAction('archiveProduct')
            ->renderAsForm()
            ->displayIf(static fn (Product $product): bool => !$product->isArchived())
            ->asWarningAction()
            // drafts were never on sale, so archiving them doesn't need a confirmation
            ->askConfirmation(static fn (Product $product): TranslatableMessage|false => $product->isPublished()
                ? new TranslatableMessage('product.confirm.archive', ['name' => (string) $product->getName()])
                : false
            );

        $statusActionGroup = ActionGroup::new('statusGroup', 'action.change_status', 'flag')
            ->addMainAction($publishAction)
            ->addAction($archiveAction)
            ->addAction($unpublishAction);

        $batchPublish = Action::new('batchPublish', 'batch.publish_selected', 'circle-check')
            ->linkToCrudAction('batchPublish')
            ->asSuccessAction()
            ->createAsBatchAction();

        $batchArchive = Action::new('batchArchive', 'batch.archive_selected', 'archive')
            ->linkToCrudAction('batchArchive')
            ->asDefaultAction()
            ->createAsBatchAction()
            ->askConfirmation('product.confirm.batch_archive');

        $batchFeatured = Action::new('batchFeatured', 'batch.mark_featured', 'star')
            ->linkToCrudAction('batchMarkAsFeatured')
            ->asWarningAction()
            ->createAsBatchAction();

        return $actions
            ->add(Crud::PAGE_INDEX, SourceCodeAction::create())
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $archiveAction)
            ->add(Crud::PAGE_INDEX, $publishAction)
            ->add(Crud::PAGE_INDEX, $unpublishAction)
            ->add(Crud::PAGE_DETAIL, $statusActionGroup)
            ->add(Crud::PAGE_EDIT, $statusActionGroup)
            ->add(Crud::PAGE_INDEX, $batchPublish)
            ->add(Crud::PAGE_INDEX, $batchArchive)
            ->add(Crud::PAGE_INDEX, $batchFeatured);
    }

    /**
     * Order lines keep a reference to the product, so products that were sold can't be
     * removed. This is checked up front because catching the foreign key exception
     * would leave the entity manager closed for the rest of the request.
     */
    public function deleteEntity(EntityManagerInterface $entityManager, object $entityInstance): void
    {
        if (0 < $entityManager->getRepository(OrderLine::class)->count(['product' => $entityInstance])) {
            $this->addFlash('danger', 'product.flash.cannot_delete');

            return;
        }

        parent::deleteEntity($entityManager, $entityInstance);
    }

    #[AdminRoute('/{entityId:product.id}/publish', options: ['methods' => ['POST']])]
    public function publishProduct(Product $product): Response
    {
        $product->setStatus(ProductStatus::Published);

        $this->entityManager->flush();
        $this->addFlash('success', 'product.flash.published');

        return $this->redirectToIndex();
    }

    #[AdminRoute('/{entityId:product.id}/unpublish', options: ['methods' => ['POST']])]
    public function unpublishProduct(Product $product): Response
    {
        $product->setStatus(ProductStatus::Draft);

        $this->entityManager->flush();
        $this->addFlash('success', 'product.flash.unpublished');

        return $this->redirectToIndex();
    }

    #[AdminRoute('/{entityId:product.id}/archive', options: ['methods' => ['POST']])]
    public function archiveProduct(Product $product): Response
    {
        $product->setStatus(ProductStatus::Archived);

        $this->entityManager->flush();
        $this->addFlash('success', 'product.flash.archived');

        return $this->redirectToIndex();
    }

    #[AdminRoute(options: ['methods' => ['POST']])]
    public function batchPublish(BatchActionDto $batchActionDto): Response
    {
        $count = $this->processBatchAction(
            $batchActionDto,
            static fn (Product $product): bool => $product->isDraft(),
            static fn (Product $product) => $product->setStatus(ProductStatus::Published)
        );

        $this->addFlash('success', new TranslatableMessage('product.flash.batch_published', ['count' => $count]));

        return $this->redirectToIndex();
    }

    #[AdminRoute(options: ['methods' => ['POST']])]
    public function batchArchive(BatchActionDto $batchActionDto): Response
    {
        $count = $this->processBatchAction(
            $batchActionDto,
            static fn (Product $product): bool => !$product->isArchived(),
            static fn (Product $product) => $product->setStatus(ProductStatus::Archived)
        );

        $this->addFlash('success', new TranslatableMessage('product.flash.batch_archived', ['count' => $count]));

        return $this->redirectToIndex();
    }

    #[AdminRoute(options: ['methods' => ['POST']])]
    public function batchMarkAsFeatured(BatchActionDto $batchActionDto): Response
    {
        $count = $this->processBatchAction(
            $batchActionDto,
            static fn (Product $product): bool => !$product->isFeatured(),
            static fn (Product $product) => $product->setIsFeatured(true)
        );

        $this->addFlash('success', new TranslatableMessage('product.flash.batch_featured', ['count' => $count]));

        return $this->redirectToIndex();
    }

    protected function getBotanicalFields(): iterable
    {
        return [];
    }

    private function getIndexFields(): array
    {
        return [
            IdField::new('id'),
            ImageField::new('image', 'product.image')
                ->setBasePath('uploads/products')
                ->setSortable(false),
            TextField::new('name', 'product.name')
                ->setTemplatePath('admin/product/_name_with_metadata.html.twig'),
            ChoiceField::new('status', 'product.status')
                ->renderAsBadges(ProductStatus::badges())
                ->setBadgeVariant(BadgeVariant::Dot),
            MoneyField::new('price', 'product.price')
                ->setCurrency('EUR')
                ->setTemplatePath('admin/product/_price.html.twig'),
            IntegerField::new('stock', 'product.stock')
                ->setTextAlign(TextAlign::RIGHT)
                ->setTemplatePath('admin/product/_stock.html.twig'),
            AssociationField::new('family', 'product.family')
                ->setSortProperty('name')
                ->setTemplatePath('admin/product/_family_badge.html.twig'),
            AssociationField::new('manager', 'product.manager')
                ->setSortProperty('fullName')
                ->setTemplatePath('admin/product/_manager_card.html.twig'),
        ];
    }

    private function getDetailAndFormFields(): iterable
    {
        yield FormField::addColumn('col-lg-8');

        yield FormField::addFieldset('product.fieldset.content', 'pen');

        yield TextField::new('name', 'product.name');

        yield SlugField::new('slug', 'product.slug')
            ->setTargetFieldName('name');

        yield ImageField::new('image', 'product.image')
            ->setUploadDir('public/uploads/products')
            ->setBasePath('uploads/products')
            ->setUploadedFileNamePattern('[YYYY]/[MM]/[slug]-[contenthash].[extension]')
            ->setFileConstraints(new Image(maxSize: '2M', mimeTypes: ['image/jpeg', 'image/png', 'image/webp']));

        yield TextareaField::new('description', 'product.description')
            ->setNumOfRows(6);

        yield FileField::new('guide', 'product.guide')
            ->setUploadDir('public/uploads/guides')
            ->setBasePath('uploads/guides')
            ->setUploadedFileNamePattern('[slug]-[contenthash].[extension]')
            ->mimeTypes('application/pdf')
            ->maxSize('5M')
            ->setHelp('product.guide_help');

        yield from $this->getBotanicalFields();

        yield FormField::addColumn('col-lg-4');

        yield FormField::addFieldset('product.fieldset.status', 'flag');

        yield ChoiceField::new('status', 'product.status')
            ->renderAsBadges(ProductStatus::badges())
            ->setBadgeVariant(BadgeVariant::Dot);

        yield BooleanField::new('isFeatured', 'product.isFeatured')
            ->renderAsSwitch(true);

        yield DateTimeField::new('createdAt', 'product.createdAt')
            ->onlyOnDetail();

        yield FormField::addFieldset('product.fieldset.pricing', 'euro');

        yield MoneyField::new('price', 'product.price')
            ->setCurrency('EUR')
            ->setFormTypeOption('constraints', [new PositiveOrZero()]);

        yield PercentField::new('discount', 'product.discount')
            ->setStoredAsFractional(false)
            ->setNumDecimals(0)
            ->setFormTypeOption('constraints', [new Range(min: 0, max: 100)]);

        yield IntegerField::new('stock', 'product.stock')
            ->setFormTypeOption('constraints', [new PositiveOrZero()]);

        yield FormField::addFieldset('product.fieldset.classification', 'folder-tree');

        yield AssociationField::new('family', 'product.family')
            ->setQueryBuilder($this->restrictToManagedFamilies(...));

        yield AssociationField::new('tags', 'product.tags')
            ->autocomplete(callback: static fn (Tag $tag): string => $tag->getName())
            ->setFormTypeOption('by_reference', false)
            ->setTemplatePath('admin/product/_tags.html.twig');

        yield AssociationField::new('supplier', 'product.supplier')
            ->autocomplete(template: 'admin/product/_supplier_autocomplete.html.twig', renderAsHtml: true);

        // no autocomplete here: it needs access to the index of UserCrudController,
        // which only admins have, so it would be broken for editors
        yield AssociationField::new('manager', 'product.manager')
            ->setQueryBuilder($this->restrictToStaff(...))
            ->setTemplatePath('admin/product/_manager_card.html.twig');
    }

    private function restrictToManagedFamilies(QueryBuilder $queryBuilder): QueryBuilder
    {
        return $queryBuilder
            ->andWhere('entity.isPlantFamily = :isPlantFamily')
            ->setParameter('isPlantFamily', $this->managesPlants())
            ->orderBy('entity.position', 'ASC');
    }

    private function restrictToStaff(QueryBuilder $queryBuilder): QueryBuilder
    {
        // roles are stored as a JSON array, so there's no way to compare them with "="
        return $queryBuilder
            ->andWhere('entity.roles LIKE :editorRole OR entity.roles LIKE :adminRole')
            ->setParameter('editorRole', '%ROLE_EDITOR%')
            ->setParameter('adminRole', '%ROLE_ADMIN%')
            ->orderBy('entity.fullName', 'ASC');
    }

    private function redirectToIndex(): Response
    {
        return $this->redirectToRoute($this->managesPlants() ? 'admin_plant_index' : 'admin_supply_index');
    }

    /**
     * @param callable(Product): bool $condition
     * @param callable(Product): void $action
     */
    private function processBatchAction(BatchActionDto $dto, callable $condition, callable $action): int
    {
        // the entity class comes from this controller because the one in the DTO is sent by the browser
        if (!$this->isCsrfTokenValid('ea-batch-action-'.$dto->getName().'-'.Product::class, $dto->getCsrfToken())) {
            throw $this->createAccessDeniedException();
        }

        $repository = $this->entityManager->getRepository(Product::class);
        $count = 0;

        foreach ($dto->getEntityIds() as $id) {
            $product = $repository->find($id);
            if ($product instanceof Product && $this->isGranted(ProductVoter::EDIT, $product) && $condition($product)) {
                $action($product);
                ++$count;
            }
        }

        $this->entityManager->flush();

        return $count;
    }
}
