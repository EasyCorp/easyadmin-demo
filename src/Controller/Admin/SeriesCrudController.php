<?php

namespace App\Controller\Admin;

use App\Admin\Field\SeriesProgressField;
use App\Entity\Series;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use Symfony\Component\HttpFoundation\Response;

class SeriesCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Series::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('series.label')
            ->setEntityLabelInPlural('series.label_plural')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setSearchFields(['title', 'description']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $markComplete = Action::new('markComplete', 'action.mark_complete', 'fa fa-check-circle')
            ->linkToCrudAction('markComplete')
            ->displayIf(static fn (Series $series): bool => !$series->isComplete())
            ->asSuccessAction();

        $markIncomplete = Action::new('markIncomplete', 'action.mark_incomplete', 'fa fa-circle')
            ->linkToCrudAction('markIncomplete')
            ->displayIf(static fn (Series $series): bool => $series->isComplete())
            ->asWarningAction();

        return $actions
            ->add(Crud::PAGE_INDEX, $markComplete)
            ->add(Crud::PAGE_INDEX, $markIncomplete)
            ->add(Crud::PAGE_DETAIL, $markComplete)
            ->add(Crud::PAGE_DETAIL, $markIncomplete)
            ->setPermission(Action::DELETE, 'ROLE_ADMIN');
    }

    public function markComplete(AdminContext $context): Response
    {
        /** @var Series $series */
        $series = $context->getEntity()->getInstance();
        $series->setIsComplete(true);

        $this->entityManager->flush();
        $this->addFlash('success', 'series.flash.marked_complete');

        return $this->redirectToRoute('admin_series_index');
    }

    public function markIncomplete(AdminContext $context): Response
    {
        /** @var Series $series */
        $series = $context->getEntity()->getInstance();
        $series->setIsComplete(false);

        $this->entityManager->flush();
        $this->addFlash('success', 'series.flash.marked_incomplete');

        return $this->redirectToRoute('admin_series_index');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->onlyOnIndex();

        yield ImageField::new('coverImage')
            ->setLabel('series.coverImage')
            ->setUploadDir('public/uploads/series')
            ->setBasePath('uploads/series')
            ->onlyOnForms();

        yield TextField::new('title')
            ->setLabel('series.title');

        yield SlugField::new('slug')
            ->setLabel('series.slug')
            ->setTargetFieldName('title')
            ->onlyOnForms();

        yield TextEditorField::new('description')
            ->setLabel('series.description')
            ->onlyOnForms();

        yield BooleanField::new('isComplete')
            ->setLabel('series.isComplete')
            ->renderAsSwitch();

        yield AssociationField::new('author')
            ->setLabel('series.author');

        yield SeriesProgressField::new('posts')
            ->setLabel('series.posts')
            ->showPercentage()
            ->showCount()
            ->onlyOnIndex();

        yield AssociationField::new('posts')
            ->setLabel('series.posts')
            ->onlyOnDetail();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(BooleanFilter::new('isComplete', 'series.isComplete'))
            ->add(EntityFilter::new('author', 'series.author'));
    }
}
