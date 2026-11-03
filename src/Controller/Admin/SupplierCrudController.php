<?php

namespace App\Controller\Admin;

use App\Admin\Action\SourceCodeAction;
use App\Admin\EntityPageTitle;
use App\Entity\Supplier;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CountryField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\CountryFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\Translation\TranslatableMessage;

class SupplierCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Supplier::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('entity.supplier')
            ->setPageTitle(Crud::PAGE_DETAIL, static fn (Supplier $supplier): TranslatableMessage => EntityPageTitle::detail('supplier', ['name' => (string) $supplier->getName()]))
            ->setPageTitle(Crud::PAGE_EDIT, static fn (Supplier $supplier): TranslatableMessage => EntityPageTitle::edit('supplier', ['name' => (string) $supplier->getName()]))
            ->setEntityLabelInPlural('entity.supplier_plural')
            ->setDefaultSort(['name' => 'ASC'])
            ->setSearchFields(['name', 'contactName', 'email'])
            ->setDefaultRowAction(Action::DETAIL);
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
            ->add(TextFilter::new('name', 'supplier.name'))
            ->add(CountryFilter::new('country', 'supplier.country'));
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, SourceCodeAction::create());
    }

    /**
     * Products keep a reference to their supplier, so the foreign key doesn't allow removing
     * a supplier that still has products. This shows a message instead of a database error.
     */
    public function deleteEntity(EntityManagerInterface $entityManager, object $entityInstance): void
    {
        if (!$entityInstance->getProducts()->isEmpty()) {
            $this->addFlash('danger', 'supplier.flash.cannot_delete');

            return;
        }

        parent::deleteEntity($entityManager, $entityInstance);
    }

    private function getIndexFields(): array
    {
        return [
            IdField::new('id'),
            TextField::new('name', 'supplier.name'),
            TextField::new('contactName', 'supplier.contactName'),
            EmailField::new('email', 'supplier.email'),
            CountryField::new('country', 'supplier.country'),
            AssociationField::new('products', 'supplier.products')
                ->setCrudController(PlantCrudController::class),
        ];
    }

    private function getDetailAndFormFields(): iterable
    {
        yield FormField::addFieldset('supplier.fieldset.details', 'file-text');

        yield TextField::new('name', 'supplier.name');

        yield CountryField::new('country', 'supplier.country');

        yield UrlField::new('website', 'supplier.website');

        yield FormField::addFieldset('supplier.fieldset.contact', 'contact');

        yield TextField::new('contactName', 'supplier.contactName');

        yield EmailField::new('email', 'supplier.email');

        yield TelephoneField::new('phone', 'supplier.phone');

        yield FormField::addFieldset('supplier.fieldset.products', 'package')
            ->onlyOnDetail();

        yield AssociationField::new('products', false)
            ->setCrudController(PlantCrudController::class)
            ->setTemplatePath('admin/product/_linked_list.html.twig')
            ->onlyOnDetail();
    }
}
