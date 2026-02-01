<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\FormFieldReference;
use App\Entity\Post;
use App\Entity\Series;
use App\Entity\Subscriber;
use App\Entity\Tag;
use App\Entity\User;
use App\Enum\CommentStatus;
use App\Enum\PostStatus;
use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use App\Repository\SubscriberRepository;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/{_locale}/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        #[Autowire('%kernel.enabled_locales%')] private array $enabledLocales,
        private PostRepository $postRepository,
        private CommentRepository $commentRepository,
        private SubscriberRepository $subscriberRepository,
    ) {
    }

    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Easyadmin Demo')
            ->setLocales($this->enabledLocales);
    }

    public function configureAssets(): Assets
    {
        return Assets::new()
            ->addAssetMapperEntry('admin')
        ;
    }

    public function configureCrud(): Crud
    {
        return Crud::new()
            ->setPaginatorPageSize(20)
            ->hideNullValues()
            ->setDateFormat('medium')
            ->setTimeFormat('short')
        ;
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linktoDashboard('menu.dashboard', 'fa fa-home');

        // Content section
        yield MenuItem::section('menu.content');

        $draftCount = $this->postRepository->count(['status' => PostStatus::Draft]);
        $postsMenuItem = MenuItem::linkToCrud('entity.blog_posts', 'fa fa-file-text-o', Post::class);
        if ($draftCount > 0) {
            $postsMenuItem->setBadge($draftCount);
        }
        yield $postsMenuItem;

        yield MenuItem::linkToCrud('entity.categories', 'fa fa-folder', Category::class);
        yield MenuItem::linkToCrud('entity.tags', 'fas fa-tags', Tag::class);
        yield MenuItem::linkToCrud('entity.series', 'fa fa-list-ol', Series::class);

        // Community section
        yield MenuItem::section('menu.community');

        $pendingCommentsCount = $this->commentRepository->count(['status' => CommentStatus::Pending]);
        $commentsMenuItem = MenuItem::linkToCrud('entity.comments', 'far fa-comments', Comment::class);
        if ($pendingCommentsCount > 0) {
            $commentsMenuItem->setBadge($pendingCommentsCount, 'danger');
        }
        yield $commentsMenuItem;

        $unconfirmedCount = $this->subscriberRepository->count(['isConfirmed' => false]);
        $subscribersMenuItem = MenuItem::linkToCrud('entity.subscribers', 'fa fa-envelope', Subscriber::class);
        if ($unconfirmedCount > 0) {
            $subscribersMenuItem->setBadge($unconfirmedCount, 'info');
        }
        yield $subscribersMenuItem;

        // Administration section
        yield MenuItem::section('menu.administration');
        yield MenuItem::linkToCrud('entity.users', 'fa fa-users', User::class);

        // Resources section
        yield MenuItem::section('menu.resources');
        yield MenuItem::linkToCrud('menu.form_field_reference', 'fa-solid fa-table-cells', FormFieldReference::class)->setAction(Action::NEW);
        yield MenuItem::linkToRoute('menu.fixtures_data', 'fa-solid fa-database', 'admin_regenerate_fixtures');

        // Links section
        yield MenuItem::section('menu.links');
        yield MenuItem::linkToUrl('menu.docs', 'fas fa-book', 'https://symfony.com/doc/current/bundles/EasyAdminBundle/index.html')->setLinkTarget('_blank');
        yield MenuItem::linkToUrl('menu.demo', 'fas fa-magic', 'https://github.com/EasyCorp/easyadmin-demo')->setLinkTarget('_blank');
        yield MenuItem::linkToUrl('menu.sponsor', 'fa fa-euro-sign', 'https://github.com/sponsors/javiereguiluz')->setLinkTarget('_blank');
    }
}
