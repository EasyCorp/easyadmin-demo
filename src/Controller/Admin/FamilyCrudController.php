<?php

namespace App\Controller\Admin;

use App\Admin\Action\SourceCodeAction;
use App\Admin\EntityPageTitle;
use App\Admin\Field\FamilyStockField;
use App\Entity\Family;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints\Image;

class FamilyCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Family::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('entity.family')
            ->setPageTitle(Crud::PAGE_DETAIL, static fn (Family $family): TranslatableMessage => EntityPageTitle::detail('family', ['name' => (string) $family->getName()]))
            ->setPageTitle(Crud::PAGE_EDIT, static fn (Family $family): TranslatableMessage => EntityPageTitle::edit('family', ['name' => (string) $family->getName()]))
            ->setEntityLabelInPlural('entity.family_plural')
            ->setDefaultSort(['position' => 'ASC'])
            ->setSearchFields(['name', 'description'])
            ->setDefaultRowAction(Action::DETAIL)
            ->overrideTemplate('crud/index', 'admin/family/index.html.twig');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->onlyOnIndex();

        yield ImageField::new('cover', 'family.cover')
            ->setUploadDir('public/uploads/families')
            ->setBasePath('uploads/families')
            ->setUploadedFileNamePattern('[slug]-[contenthash].[extension]')
            ->setFileConstraints(new Image(maxSize: '2M', mimeTypes: ['image/jpeg', 'image/png', 'image/webp']))
            ->setSortable(false);

        yield TextField::new('name', 'family.name');

        yield SlugField::new('slug', 'family.slug')
            ->setTargetFieldName('name')
            ->onlyOnForms();

        yield TextareaField::new('description', 'family.description')
            ->hideOnIndex();

        yield IntegerField::new('position', 'family.position');

        yield BooleanField::new('isPlantFamily', 'family.isPlantFamily')
            ->renderAsSwitch(false)
            ->setHelp('family.isPlantFamily_help');

        yield FamilyStockField::new('products', 'family.stock')
            ->showPercentage()
            ->showCount()
            ->onlyOnIndex();

        yield AssociationField::new('products', 'family.products')
            ->setCrudController(PlantCrudController::class)
            ->setTemplatePath('admin/product/_linked_list.html.twig')
            ->onlyOnDetail();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('name', 'family.name'))
            ->add(BooleanFilter::new('isPlantFamily', 'family.isPlantFamily'));
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, SourceCodeAction::create());
    }

    /**
     * Products must belong to a family, so the foreign key doesn't allow removing a
     * family that still has products. This shows a message instead of a database error.
     */
    public function deleteEntity(EntityManagerInterface $entityManager, object $entityInstance): void
    {
        if (!$entityInstance->getProducts()->isEmpty()) {
            $this->addFlash('danger', 'family.flash.cannot_delete');

            return;
        }

        parent::deleteEntity($entityManager, $entityInstance);
    }
}
