<?php

namespace App\Controller\Admin;

use App\Admin\Action\SourceCodeAction;
use App\Admin\EntityPageTitle;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\TextAlign;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ArrayFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\BadgeVariant;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints\Image;

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
            ->setPageTitle(Crud::PAGE_DETAIL, static fn (User $user): TranslatableMessage => EntityPageTitle::detail('user', ['name' => (string) $user->getFullName()]))
            ->setPageTitle(Crud::PAGE_EDIT, static fn (User $user): TranslatableMessage => EntityPageTitle::edit('user', ['name' => (string) $user->getFullName()]))
            ->setEntityLabelInPlural('user.label_plural')
            ->setDefaultSort(['fullName' => 'ASC'])
            ->setSearchFields(['fullName', 'email'])
            ->setDefaultRowAction(Action::DETAIL);
    }

    public function configureActions(Actions $actions): Actions
    {
        // the session user is excluded because Symfony doesn't allow impersonating yourself
        $impersonate = Action::new('impersonate', 'action.impersonate', 'venetian-mask')
            ->linkToUrl(fn (User $user): string => $this->generateUrl('admin', ['_switch_user' => $user->getUserIdentifier()]))
            ->displayIf(fn (User $user): bool => $user->getUserIdentifier() !== $this->getUser()?->getUserIdentifier());

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $impersonate)
            ->add(Crud::PAGE_INDEX, SourceCodeAction::create())
            ->setPermission(Action::INDEX, 'ROLE_ADMIN')
            ->setPermission(Action::DETAIL, 'ROLE_ADMIN')
            ->setPermission(Action::NEW, 'ROLE_ADMIN')
            ->setPermission(Action::EDIT, 'ROLE_ADMIN')
            ->setPermission('impersonate', 'ROLE_ALLOWED_TO_SWITCH');
    }

    public function configureFields(string $pageName): iterable
    {
        // the template falls back to Gravatar when the user has no uploaded avatar; SVG files
        // aren't allowed because they can run scripts and this demo serves them from its own origin
        yield ImageField::new('avatar')
            ->setLabel('user.avatar')
            ->setBasePath('uploads/avatars')
            ->setUploadDir('public/uploads/avatars')
            ->setUploadedFileNamePattern('[slug]-[contenthash].[extension]')
            ->setFileConstraints(new Image(maxSize: '2M', mimeTypes: ['image/jpeg', 'image/png', 'image/webp']))
            ->setTemplatePath('admin/user/_avatar.html.twig')
            ->setSortable(false)
            ->setRequired(false);

        yield IdField::new('id')
            ->onlyOnIndex();

        yield TextField::new('fullName')
            ->setLabel('user.fullName');

        yield EmailField::new('email')
            ->setLabel('user.email');

        yield ChoiceField::new('roles')
            ->setLabel('user.roles')
            ->allowMultipleChoices()
            ->renderAsBadges([
                'ROLE_ADMIN' => 'info',
                'ROLE_EDITOR' => 'warning',
                'ROLE_USER' => 'secondary',
            ])
            ->setBadgeVariant(BadgeVariant::Outline)
            ->setChoices([
                'user.role.admin' => 'ROLE_ADMIN',
                'user.role.editor' => 'ROLE_EDITOR',
                'user.role.user' => 'ROLE_USER',
            ]);

        yield BooleanField::new('isVerified')
            ->setLabel('user.isVerified')
            ->renderAsSwitch(false)
            ->hideValueWhenFalse();

        yield DateTimeField::new('lastLoginAt')
            ->setLabel('user.lastLoginAt')
            ->onlyOnDetail();

        yield AssociationField::new('managedProducts')
            ->setLabel('user.managedProducts')
            ->setCrudController(PlantCrudController::class)
            ->onlyOnIndex()
            ->setTemplatePath('admin/user/_managed_products_summary.html.twig')
            ->setTextAlign(TextAlign::LEFT);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('fullName', 'user.fullName'))
            ->add(TextFilter::new('email', 'user.email'))
            ->add(ArrayFilter::new('roles', 'user.roles')
                ->setChoices([
                    'user.role.admin' => 'ROLE_ADMIN',
                    'user.role.editor' => 'ROLE_EDITOR',
                    'user.role.user' => 'ROLE_USER',
                ])
                // the choice labels of this filter aren't translated unless a domain is set
                ->setFormTypeOption('value_type_options.choice_translation_domain', 'messages'))
            ->add(BooleanFilter::new('isVerified', 'user.isVerified'))
            ->add(DateTimeFilter::new('lastLoginAt', 'user.lastLoginAt'));
    }

    public function deleteEntity(EntityManagerInterface $entityManager, object $entityInstance): void
    {
        // the products stay in the catalog without a manager instead of pointing to a removed user
        foreach ($entityInstance->getManagedProducts() as $product) {
            $product->setManager(null);
        }

        parent::deleteEntity($entityManager, $entityInstance);
    }
}
