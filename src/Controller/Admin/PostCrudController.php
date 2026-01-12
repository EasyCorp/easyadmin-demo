<?php

namespace App\Controller\Admin;

use App\Admin\Filter\AuthorWithMinPostsFilter;
use App\Entity\Post;
use App\Enum\PostStatus;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\ActionGroup;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\HttpFoundation\Response;

class PostCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Post::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('post.label')
            ->setEntityLabelInPlural('post.label_plural')
            ->setDefaultSort(['publishedAt' => 'DESC'])
            ->setSearchFields(['title', 'summary', 'content', 'author.fullName']);
    }

    public function configureFields(string $pageName): iterable
    {
        // === INDEX PAGE ===
        yield IdField::new('id')
            ->onlyOnIndex();

        yield TextField::new('title')
            ->setLabel('post.title')
            ->setTemplatePath('admin/post/_title_with_metadata.html.twig')
            ->onlyOnIndex();

        yield ChoiceField::new('status')
            ->setLabel('post.status')
            ->setChoices(PostStatus::choices())
            ->renderAsBadges(PostStatus::badges())
            ->onlyOnIndex();

        yield AssociationField::new('author')
            ->setLabel('post.author')
            ->setTemplatePath('admin/post/_author_card.html.twig')
            ->onlyOnIndex();

        yield AssociationField::new('category')
            ->setLabel('post.category')
            ->setTemplatePath('admin/post/_category_badge.html.twig')
            ->onlyOnIndex();

        yield BooleanField::new('isFeatured')
            ->setLabel('post.isFeatured')
            ->renderAsSwitch(false)
            ->hideValueWhenFalse()
            ->onlyOnIndex();

        yield DateTimeField::new('publishedAt')
            ->setLabel('post.publishedAt')
            ->onlyOnIndex();

        // === DETAIL & FORM PAGES (two-column layout) ===

        // Main column (content)
        yield FormField::addColumn('col-lg-8')
            ->hideOnIndex();

        yield FormField::addFieldset('post.fieldset.content', 'fa fa-pen')
            ->hideOnIndex();

        yield TextField::new('title')
            ->setLabel('post.title')
            ->hideOnIndex();

        yield SlugField::new('slug')
            ->setLabel('post.slug')
            ->setTargetFieldName('title')
            ->hideOnIndex();

        yield ImageField::new('featuredImage')
            ->setLabel('post.featuredImage')
            ->setUploadDir('public/uploads/posts')
            ->setBasePath('uploads/posts')
            ->setUploadedFileNamePattern('[year]/[month]/[slug]-[contenthash].[extension]')
            ->hideOnIndex();

        yield TextEditorField::new('content')
            ->setLabel('post.content')
            ->setNumOfRows(20)
            ->onlyOnForms();

        yield TextField::new('content')
            ->setLabel('post.content')
            ->onlyOnDetail()
            ->renderAsHtml();

        yield TextareaField::new('summary')
            ->setLabel('post.summary')
            ->setNumOfRows(3)
            ->hideOnIndex();

        // Sidebar column (metadata)
        yield FormField::addColumn('col-lg-4')
            ->hideOnIndex();

        // Status fieldset
        yield FormField::addFieldset('post.fieldset.status', 'fa fa-flag')
            ->hideOnIndex();

        yield ChoiceField::new('status')
            ->setLabel('post.status')
            ->setChoices(PostStatus::choices())
            ->renderAsBadges(PostStatus::badges())
            ->hideOnIndex();

        yield BooleanField::new('isFeatured')
            ->setLabel('post.isFeatured')
            ->renderAsSwitch(true)
            ->hideOnIndex();

        yield DateTimeField::new('publishedAt')
            ->setLabel('post.publishedAt')
            ->hideOnIndex();

        yield DateTimeField::new('scheduledAt')
            ->setLabel('post.scheduledAt')
            ->setHelp('post.scheduledAt_help')
            ->hideOnIndex();

        // Classification fieldset
        yield FormField::addFieldset('post.fieldset.classification', 'fa fa-folder-tree')
            ->hideOnIndex();

        yield AssociationField::new('author')
            ->setLabel('post.author')
            ->setSortProperty('fullName')
            ->autocomplete()
            ->hideOnIndex();

        yield AssociationField::new('category')
            ->setLabel('post.category')
            ->setSortProperty('name')
            ->hideOnIndex();

        yield AssociationField::new('tags')
            ->setLabel('post.tags')
            ->autocomplete()
            ->setFormTypeOption('by_reference', false)
            ->hideOnIndex();

        // Series fieldset
        yield FormField::addFieldset('post.fieldset.series', 'fa fa-layer-group')
            ->hideOnIndex()
            ->collapsible();

        yield AssociationField::new('series')
            ->setLabel('post.series')
            ->hideOnIndex();

        yield IntegerField::new('seriesPosition')
            ->setLabel('post.seriesPosition')
            ->setHelp('post.seriesPosition_help')
            ->hideOnIndex();

        // Statistics fieldset (detail only)
        yield FormField::addFieldset('post.fieldset.statistics', 'fa fa-chart-line')
            ->onlyOnDetail();

        yield IntegerField::new('viewCount')
            ->setLabel('post.viewCount')
            ->setThousandsSeparator(',')
            ->onlyOnDetail();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('title', 'post.title'))
            ->add(EntityFilter::new('author', 'post.author'))
            ->add(AuthorWithMinPostsFilter::new('authorMinPosts', 'filter.author_min_posts'))
            ->add(EntityFilter::new('category', 'post.category'))
            ->add(EntityFilter::new('tags', 'post.tags'))
            ->add(ChoiceFilter::new('status', 'post.status')->renderExpanded()->setTranslatableChoices(PostStatus::filterChoices()))
            ->add(BooleanFilter::new('isFeatured', 'post.isFeatured'))
            ->add(DateTimeFilter::new('publishedAt', 'post.publishedAt'));
    }

    public function configureActions(Actions $actions): Actions
    {
        // Permissions: Only ADMIN can delete posts
        $actions
            ->setPermission(Action::DELETE, 'ROLE_ADMIN');

        // Status actions
        $publishAction = Action::new('publish', 'action.publish', 'fa fa-check-circle')
            ->linkToCrudAction('publishPost')
            ->displayIf(static fn (Post $post): bool => $post->isDraft())
            ->asSuccessAction();

        $unpublishAction = Action::new('unpublish', 'action.unpublish', 'fa fa-eye-slash')
            ->linkToCrudAction('unpublishPost')
            ->displayIf(static fn (Post $post): bool => $post->isPublished())
            ->asDangerAction();

        $archiveAction = Action::new('archive', 'action.archive', 'fa fa-archive')
            ->linkToCrudAction('archivePost')
            ->displayIf(static fn (Post $post): bool => !$post->isArchived())
            ->asWarningAction();

        // Action Group for status changes (detail page) - with split button
        $statusActionGroup = ActionGroup::new('statusGroup', 'action.change_status', 'fa fa-flag')
            ->addMainAction($publishAction)
            ->addAction($archiveAction)
            ->addAction($unpublishAction);

        $viewOnSiteAction = Action::new('viewOnSite', 'action.view_on_site', 'fa fa-external-link')
            ->linkToUrl(fn (Post $post): string => '/blog/'.$post->getSlug())
            ->setHtmlAttributes(['target' => '_blank'])
            ->displayIf(static fn (Post $post): bool => $post->isPublished());

        // Batch actions
        $batchPublish = Action::new('batchPublish', 'batch.publish_selected', 'fa fa-check-circle')
            ->linkToCrudAction('batchPublish')
            ->asSuccessAction()
            ->createAsBatchAction();

        $batchArchive = Action::new('batchArchive', 'batch.archive_selected', 'fa fa-archive')
            ->linkToCrudAction('batchArchive')
            ->asDefaultAction()
            ->createAsBatchAction();

        $batchFeatured = Action::new('batchFeatured', 'batch.mark_featured', 'fa fa-star')
            ->linkToCrudAction('batchMarkAsFeatured')
            ->asWarningAction()
            ->createAsBatchAction();

        return $actions
            // Index page: individual actions (in dropdown by default)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $viewOnSiteAction)
            ->add(Crud::PAGE_INDEX, $archiveAction)
            ->add(Crud::PAGE_INDEX, $publishAction)
            ->add(Crud::PAGE_INDEX, $unpublishAction)
            // Detail & Edit pages: grouped status actions with split button
            ->add(Crud::PAGE_DETAIL, $viewOnSiteAction)
            ->add(Crud::PAGE_DETAIL, $statusActionGroup)
            ->add(Crud::PAGE_EDIT, $statusActionGroup)
            // Batch actions
            ->add(Crud::PAGE_INDEX, $batchPublish)
            ->add(Crud::PAGE_INDEX, $batchArchive)
            ->add(Crud::PAGE_INDEX, $batchFeatured);
    }

    public function publishPost(AdminContext $context): Response
    {
        /** @var Post $post */
        $post = $context->getEntity()->getInstance();
        $post->setStatus(PostStatus::Published);
        $post->setPublishedAt(new \DateTimeImmutable());

        $this->entityManager->flush();
        $this->addFlash('success', 'post.flash.published');

        return $this->redirectToRoute('admin_post_index');
    }

    public function unpublishPost(AdminContext $context): Response
    {
        /** @var Post $post */
        $post = $context->getEntity()->getInstance();
        $post->setStatus(PostStatus::Draft);

        $this->entityManager->flush();
        $this->addFlash('success', 'post.flash.unpublished');

        return $this->redirectToRoute('admin_post_index');
    }

    public function archivePost(AdminContext $context): Response
    {
        /** @var Post $post */
        $post = $context->getEntity()->getInstance();
        $post->setStatus(PostStatus::Archived);

        $this->entityManager->flush();
        $this->addFlash('success', 'post.flash.archived');

        return $this->redirectToRoute('admin_post_index');
    }

    public function batchPublish(BatchActionDto $batchActionDto): Response
    {
        $count = $this->processBatchAction(
            $batchActionDto,
            static fn (Post $post): bool => $post->isDraft(),
            static function (Post $post): void {
                $post->setStatus(PostStatus::Published);
                $post->setPublishedAt(new \DateTimeImmutable());
            }
        );

        $this->addFlash('success', sprintf('%d post(s) published.', $count));

        return $this->redirect($batchActionDto->getReferrerUrl());
    }

    public function batchArchive(BatchActionDto $batchActionDto): Response
    {
        $count = $this->processBatchAction(
            $batchActionDto,
            static fn (Post $post): bool => !$post->isArchived(),
            static fn (Post $post) => $post->setStatus(PostStatus::Archived)
        );

        $this->addFlash('success', sprintf('%d post(s) archived.', $count));

        return $this->redirect($batchActionDto->getReferrerUrl());
    }

    public function batchMarkAsFeatured(BatchActionDto $batchActionDto): Response
    {
        $count = $this->processBatchAction(
            $batchActionDto,
            static fn (Post $post): bool => !$post->isFeatured(),
            static fn (Post $post) => $post->setIsFeatured(true)
        );

        $this->addFlash('success', sprintf('%d post(s) marked as featured.', $count));

        return $this->redirect($batchActionDto->getReferrerUrl());
    }

    /**
     * @param callable(Post): bool $condition
     * @param callable(Post): void $action
     */
    private function processBatchAction(BatchActionDto $dto, callable $condition, callable $action): int
    {
        $repository = $this->entityManager->getRepository(Post::class);
        $count = 0;

        foreach ($dto->getEntityIds() as $id) {
            $post = $repository->find($id);
            if ($post instanceof Post && $condition($post)) {
                $action($post);
                ++$count;
            }
        }

        $this->entityManager->flush();

        return $count;
    }
}
