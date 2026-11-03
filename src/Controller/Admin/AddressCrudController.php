<?php

namespace App\Controller\Admin;

use App\Entity\Address;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\CountryField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class AddressCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Address::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        // this controller only provides the form embedded in the customer form;
        // without this, its routes would list every address on their own pages
        return $actions->disable(Action::INDEX, Action::NEW, Action::DETAIL, Action::EDIT, Action::DELETE);
    }

    public function configureFields(string $pageName): iterable
    {
        // the embedded form is optional, so browsers don't require any of these fields; the address
        // setters don't accept null, and this avoids an error when only some fields are filled in
        yield TextField::new('street')
            ->setLabel('address.street')
            ->setEmptyData('')
            ->setColumns(12);

        yield TextField::new('postalCode')
            ->setLabel('address.postalCode')
            ->setEmptyData('')
            ->setColumns(4);

        yield TextField::new('city')
            ->setLabel('address.city')
            ->setEmptyData('')
            ->setColumns(8);

        yield CountryField::new('country')
            ->setLabel('address.country')
            ->setFormTypeOption('setter', static function (Address $address, ?string $country): void {
                $address->setCountry((string) $country);
            })
            ->setColumns(12);
    }
}
