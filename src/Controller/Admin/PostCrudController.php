<?php

namespace App\Controller\Admin;

use App\Admin\Filter\AuthorWithMinPostsFilter;
use App\Entity\Category;
use App\Entity\Post;
use App\Entity\Tag;
use App\Enum\PostStatus;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
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
            ->setSearchFields(['title', 'summary', 'content', 'author.fullName'])
            ->setDefaultRowAction(Action::EDIT);
    }

    public function configureFields(string $pageName): iterable
    {
        if (Crud::PAGE_INDEX === $pageName) {
            return $this->getIndexFields();
        }

        return $this->getDetailAndFormFields();
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
            ->asWarningAction()
            ->askConfirmation('post.confirm.archive');

        // Action Group for status changes (detail page) - with split button
        $statusActionGroup = ActionGroup::new('statusGroup', 'action.change_status', 'fa fa-flag')
            ->addMainAction($publishAction)
            ->addAction($archiveAction)
            ->addAction($unpublishAction);

        $viewOnSiteAction = Action::new('viewOnSite', 'action.view_on_site', 'fa fa-external-link')
            ->linkToUrl(static fn (Post $post): string => '/blog/'.$post->getSlug())
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
            ->createAsBatchAction()
            ->askConfirmation('post.confirm.batch_archive');

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

    #[AdminRoute(path: '{entityId}/publish', name: 'publish')]
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

    #[AdminRoute(path: '{entityId}/unpublish', name: 'unpublish')]
    public function unpublishPost(AdminContext $context): Response
    {
        /** @var Post $post */
        $post = $context->getEntity()->getInstance();
        $post->setStatus(PostStatus::Draft);

        $this->entityManager->flush();
        $this->addFlash('success', 'post.flash.unpublished');

        return $this->redirectToRoute('admin_post_index');
    }

    #[AdminRoute(path: '{entityId}/archive', name: 'archive')]
    public function archivePost(AdminContext $context): Response
    {
        /** @var Post $post */
        $post = $context->getEntity()->getInstance();
        $post->setStatus(PostStatus::Archived);

        $this->entityManager->flush();
        $this->addFlash('success', 'post.flash.archived');

        return $this->redirectToRoute('admin_post_index');
    }

    #[AdminRoute(path: 'batch-publish', name: 'batch_publish', options: ['methods' => ['POST']])]
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

    #[AdminRoute(path: 'batch-archive', name: 'batch_archive', options: ['methods' => ['POST']])]
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

    #[AdminRoute(path: 'batch-featured', name: 'batch_featured', options: ['methods' => ['POST']])]
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

    private function getIndexFields(): array
    {
        return [
            IdField::new('id'),
            TextField::new('title', 'post.title')
                ->setTemplatePath('admin/post/_title_with_metadata.html.twig'),
            ChoiceField::new('status', 'post.status')
                ->setChoices(PostStatus::choices())->renderAsBadges(PostStatus::badges()),
            AssociationField::new('author', 'post.author')
                ->setTemplatePath('admin/post/_author_card.html.twig'),
            AssociationField::new('category', 'post.category')
                ->setTemplatePath('admin/post/_category_badge.html.twig'),
            BooleanField::new('isFeatured', 'post.isFeatured')
                ->renderAsSwitch(false)
                ->hideValueWhenFalse(),
        ];
    }

    private function getDetailAndFormFields(): iterable
    {
        // Main column (content)
        yield FormField::addColumn('col-lg-8');

        yield FormField::addFieldset('post.fieldset.content', 'fa fa-pen');

        yield TextField::new('title', 'post.title');

        yield SlugField::new('slug', 'post.slug')
            ->setTargetFieldName('title');

        yield ImageField::new('featuredImage', 'post.featuredImage')
            ->setUploadDir('public/uploads/posts')
            ->setBasePath('uploads/posts')
            ->setUploadedFileNamePattern('[year]/[month]/[slug]-[contenthash].[extension]');

        yield TextEditorField::new('content', 'post.content')
            ->setNumOfRows(20)
            ->onlyOnForms();

        yield TextField::new('content', 'post.content')
            ->onlyOnDetail()
            ->renderAsHtml();

        yield TextareaField::new('summary', 'post.summary')
            ->setNumOfRows(3);

        // Sidebar column (metadata)
        yield FormField::addColumn('col-lg-4');

        yield FormField::addFieldset('post.fieldset.status', 'fa fa-flag');

        yield ChoiceField::new('status', 'post.status')
            ->setChoices(PostStatus::choices())
            ->renderAsBadges(PostStatus::badges())
            ->setPreferredChoices([PostStatus::Published]);

        yield BooleanField::new('isFeatured', 'post.isFeatured')
            ->renderAsSwitch(true);

        yield DateTimeField::new('publishedAt', 'post.publishedAt');

        yield DateTimeField::new('scheduledAt', 'post.scheduledAt')
            ->setHelp('post.scheduledAt_help');

        // Classification fieldset
        yield FormField::addFieldset('post.fieldset.classification', 'fa fa-folder-tree');

        yield AssociationField::new('author', 'post.author')
            ->setSortProperty('fullName')
            ->autocomplete(template: 'admin/post/_author_autocomplete.html.twig', renderAsHtml: true);

        yield AssociationField::new('category', 'post.category')
            ->setSortProperty('name')
            ->autocomplete(callback: static fn (Category $c): string => sprintf('%s %s', $c->getIcon(), $c->getName()));

        yield AssociationField::new('tags', 'post.tags')
            ->autocomplete(callback: static fn (Tag $tag): string => $tag->getName())
            ->setFormTypeOption('by_reference', false);

        yield FormField::addFieldset('post.fieldset.series', 'fa fa-layer-group')->collapsible();

        yield AssociationField::new('series', 'post.series');

        yield IntegerField::new('seriesPosition', 'post.seriesPosition')
            ->setHelp('post.seriesPosition_help');

        // Statistics fieldset (detail only)
        yield FormField::addFieldset('post.fieldset.statistics', 'fa fa-chart-line')
            ->onlyOnDetail();

        yield IntegerField::new('viewCount', 'post.viewCount')
            ->setThousandsSeparator(',')
            ->onlyOnDetail();
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
