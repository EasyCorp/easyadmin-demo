<?php

namespace App\Controller\Admin;

use App\Entity\Tag;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class TagCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Tag::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('tag.label')
            ->setEntityLabelInPlural('tag.label_plural')
            ->setDefaultSort(['name' => 'ASC'])
            ->setSearchFields(['name'])
            ->showEntityActionsInlined();
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->onlyOnIndex();

        yield TextField::new('name')
            ->setLabel('tag.name')
            ->setTemplatePath('admin/tag/_name_badge.html.twig')
            ->onlyOnIndex();

        yield TextField::new('name')
            ->setLabel('tag.name')
            ->hideOnIndex();

        yield AssociationField::new('posts')
            ->setLabel('tag.posts')
            ->setTemplatePath('admin/tag/_posts_popularity.html.twig')
            ->onlyOnIndex();

        yield AssociationField::new('posts')
            ->setLabel('tag.posts')
            ->onlyOnDetail();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('name', 'tag.name'));
    }

    public function configureActions(Actions $actions): Actions
    {
        $viewPosts = Action::new('viewPosts', 'action.view_posts', 'fa fa-newspaper')
            ->linkToUrl(fn (Tag $tag): string => $this->urlGenerator->generate('admin_post_index', [
                'filters[tags][value]' => $tag->getId(),
                'filters[tags][comparison]' => 'eq',
            ]));

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $viewPosts)
            ->setPermission(Action::DELETE, 'ROLE_ADMIN');
    }
}
