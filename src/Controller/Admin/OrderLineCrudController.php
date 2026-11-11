<?php

namespace App\Controller\Admin;

use App\Entity\OrderLine;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;

class OrderLineCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return OrderLine::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        // this controller only provides the form of each entry of the order lines collection
        return $actions->disable(Action::INDEX, Action::NEW, Action::DETAIL, Action::EDIT, Action::DELETE);
    }

    public function configureFields(string $pageName): iterable
    {
        // a regular dropdown instead of autocomplete() because the autocomplete endpoint
        // of PlantCrudController only returns plants and orders also include supplies
        yield AssociationField::new('product')
            ->setLabel('order.line.product')
            ->setCrudController(PlantCrudController::class)
            ->setColumns(6);

        yield IntegerField::new('quantity')
            ->setLabel('order.line.quantity')
            ->setFormTypeOption('attr.min', 1)
            ->setColumns(3);

        yield MoneyField::new('unitPrice')
            ->setLabel('order.line.unitPrice')
            ->setCurrency('EUR')
            ->setColumns(3);
    }
}
