<?php

namespace App\Controller\Admin;

use App\Admin\EntityPageTitle;
use App\Entity\Product;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\CountryField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\CountryFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\Translation\TranslatableMessage;

class PlantCrudController extends AbstractProductCrudController
{
    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setEntityLabelInSingular('entity.plant')
            ->setPageTitle(Crud::PAGE_DETAIL, static fn (Product $product): TranslatableMessage => EntityPageTitle::detail('plant', ['name' => (string) $product->getName()]))
            ->setPageTitle(Crud::PAGE_EDIT, static fn (Product $product): TranslatableMessage => EntityPageTitle::edit('plant', ['name' => (string) $product->getName()]))
            ->setEntityLabelInPlural('entity.plant_plural');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return parent::configureFilters($filters)
            ->add(TextFilter::new('botanicalName', 'product.botanicalName'))
            ->add(CountryFilter::new('originCountry', 'product.originCountry'));
    }

    protected function managesPlants(): bool
    {
        return true;
    }

    protected function getBotanicalFields(): iterable
    {
        yield FormField::addFieldset('product.fieldset.botanical', 'sprout');

        yield TextField::new('botanicalName', 'product.botanicalName');

        yield NumberField::new('height', 'product.height')
            ->setNumDecimals(0);

        yield CountryField::new('originCountry', 'product.originCountry');
    }
}
