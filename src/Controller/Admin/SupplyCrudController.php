<?php

namespace App\Controller\Admin;

use App\Admin\EntityPageTitle;
use App\Entity\Product;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use Symfony\Component\Translation\TranslatableMessage;

class SupplyCrudController extends AbstractProductCrudController
{
    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setEntityLabelInSingular('entity.supply')
            ->setPageTitle(Crud::PAGE_DETAIL, static fn (Product $product): TranslatableMessage => EntityPageTitle::detail('supply', ['name' => (string) $product->getName()]))
            ->setPageTitle(Crud::PAGE_EDIT, static fn (Product $product): TranslatableMessage => EntityPageTitle::edit('supply', ['name' => (string) $product->getName()]))
            ->setEntityLabelInPlural('entity.supply_plural');
    }

    protected function managesPlants(): bool
    {
        return false;
    }
}
