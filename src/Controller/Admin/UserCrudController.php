<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AvatarField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('user.label')
            ->setEntityLabelInPlural('user.label_plural')
            ->setDefaultSort(['fullName' => 'ASC'])
            ->setSearchFields(['fullName', 'email', 'username']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->setPermission(Action::DELETE, 'ROLE_ADMIN');
    }

    public function configureFields(string $pageName): iterable
    {
        yield AvatarField::new('email')
            ->setIsGravatarEmail()
            ->setGravatarDefaultImage('identicon')
            ->setHeight(40)
            ->onlyOnIndex()
            ->setLabel('user.avatar');

        yield IdField::new('id')
            ->onlyOnIndex();

        yield TextField::new('fullName')
            ->setLabel('user.fullName');

        yield TextField::new('username')
            ->setLabel('user.username');

        yield EmailField::new('email')
            ->setLabel('user.email');

        yield TextField::new('password')
            ->onlyOnForms()
            ->setLabel('user.password');

        yield ChoiceField::new('roles')
            ->setLabel('user.roles')
            ->allowMultipleChoices()
            ->renderAsBadges([
                'ROLE_ADMIN' => 'danger',
                'ROLE_EDITOR' => 'warning',
                'ROLE_USER' => 'secondary',
            ])
            ->setChoices([
                'Admin' => 'ROLE_ADMIN',
                'Editor' => 'ROLE_EDITOR',
                'User' => 'ROLE_USER',
            ]);

        yield BooleanField::new('isVerified')
            ->setLabel('user.isVerified')
            ->renderAsSwitch(false)
            ->hideValueWhenFalse();

        yield TextareaField::new('bio')
            ->setLabel('user.bio')
            ->onlyOnForms()
            ->setNumOfRows(4);

        yield UrlField::new('website')
            ->setLabel('user.website')
            ->onlyOnForms();

        yield TextField::new('twitterHandle')
            ->setLabel('user.twitterHandle')
            ->onlyOnForms()
            ->setHelp('user.twitterHandle_help');

        yield DateTimeField::new('lastLoginAt')
            ->setLabel('user.lastLoginAt')
            ->onlyOnDetail();

        yield AssociationField::new('posts')
            ->setLabel('user.posts')
            ->onlyOnIndex()
            ->setTemplatePath('admin/user/_posts_summary.html.twig');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('fullName', 'user.fullName'))
            ->add(TextFilter::new('email', 'user.email'))
            ->add(ChoiceFilter::new('roles', 'user.roles')->setChoices([
                'Admin' => 'ROLE_ADMIN',
                'Editor' => 'ROLE_EDITOR',
                'User' => 'ROLE_USER',
            ]))
            ->add(BooleanFilter::new('isVerified', 'user.isVerified'))
            ->add(DateTimeFilter::new('lastLoginAt', 'user.lastLoginAt'));
    }
}
