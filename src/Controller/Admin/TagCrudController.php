<?php

namespace App\Controller\Admin;

use App\Admin\Action\SourceCodeAction;
use App\Entity\Product;
use App\Entity\Tag;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\TextAlign;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Contracts\Translation\TranslatorInterface;

class TagCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Tag::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('entity.tag')
            ->setEntityLabelInPlural('entity.tag_plural')
            ->setDefaultSort(['name' => 'ASC'])
            ->setSearchFields(['name'])
            ->showEntityActionsInlined()
            ->setDefaultRowAction('viewProducts');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->onlyOnIndex();

        yield TextField::new('name', 'tag.name')
            ->setTemplatePath('admin/tag/_name_badge.html.twig')
            ->onlyOnIndex();

        yield TextField::new('name', 'tag.name')
            ->hideOnIndex();

        yield AssociationField::new('products', 'tag.products')
            ->setCrudController(PlantCrudController::class)
            ->setTemplatePath('admin/tag/_products_popularity.html.twig')
            ->setTextAlign(TextAlign::LEFT)
            ->onlyOnIndex();

        yield AssociationField::new('products', 'tag.products')
            ->setCrudController(PlantCrudController::class)
            ->setTemplatePath('admin/product/_linked_list.html.twig')
            ->onlyOnDetail();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('name', 'tag.name'));
    }

    public function configureActions(Actions $actions): Actions
    {
        $viewProducts = Action::new('viewProducts', 'action.view_products', 'package')
            ->linkToUrl(fn (Tag $tag): string => $this->generateUrl($this->getListingRouteFor($tag), [
                'filters' => ['tags' => ['comparison' => '=', 'value' => [$tag->getId()]]],
            ]));

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $viewProducts)
            ->add(Crud::PAGE_INDEX, SourceCodeAction::create())
            ->update(Crud::PAGE_INDEX, Action::EDIT, fn (Action $action): Action => $this->asIconOnlyAction($action, 'pencil', 'action.edit'))
            ->update(Crud::PAGE_INDEX, Action::DETAIL, fn (Action $action): Action => $this->asIconOnlyAction($action, 'eye', 'action.detail'))
            ->update(Crud::PAGE_INDEX, Action::DELETE, fn (Action $action): Action => $this->asIconOnlyAction($action, 'trash-2', 'action.delete'))
            ->setPermission('viewProducts', 'PUBLIC_ACCESS');
    }

    /**
     * Without a visible label, the title and the ARIA label keep the action understandable.
     */
    private function asIconOnlyAction(Action $action, string $icon, string $translationId): Action
    {
        $label = $this->translator->trans($translationId, domain: 'EasyAdminBundle');

        return $action
            ->setIcon($icon)
            ->setLabel(false)
            ->setHtmlAttributes(['title' => $label, 'aria-label' => $label]);
    }

    /**
     * Tags like "Organic" are only used by supplies, and they would show an empty plants listing.
     */
    private function getListingRouteFor(Tag $tag): string
    {
        $hasPlants = $tag->getProducts()->exists(static fn (int $key, Product $product): bool => $product->getFamily()->isPlantFamily());

        return $hasPlants ? 'admin_plant_index' : 'admin_supply_index';
    }
}
