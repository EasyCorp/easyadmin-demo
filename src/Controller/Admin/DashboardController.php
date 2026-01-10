<?php

namespace App\Controller\Admin;

use App\Entity\Comment;
use App\Entity\FormFieldReference;
use App\Entity\Post;
use App\Entity\Tag;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/{_locale<%app.supported_locales%>}/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('New Easyadmin Demo');
    }

    public function configureAssets(): Assets
    {
        return Assets::new()
            ->addAssetMapperEntry('admin')
        ;
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linktoDashboard('menu.dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('entity.users', 'fa fa-users', User::class);
        yield MenuItem::linkToCrud('entity.blog_posts', 'fa fa-file-text-o', Post::class);
        yield MenuItem::linkToCrud('entity.comments', 'far fa-comments', Comment::class);
        yield MenuItem::linkToCrud('entity.tags', 'fas fa-tags', Tag::class);

        yield MenuItem::section('menu.resources');
        yield MenuItem::linkToCrud('menu.form_field_reference', 'fa-solid fa-table-cells', FormFieldReference::class)->setAction(Action::NEW);
        yield MenuItem::linkToRoute('menu.fixtures_data', 'fa-solid fa-database', 'admin_regenerate_fixtures');

        yield MenuItem::section('menu.links');
        yield MenuItem::linkToUrl('menu.docs', 'fas fa-book', 'https://symfony.com/doc/current/bundles/EasyAdminBundle/index.html')->setLinkTarget('_blank');
        yield MenuItem::linkToUrl('menu.demo', 'fas fa-magic', 'https://github.com/EasyCorp/easyadmin-demo')->setLinkTarget('_blank');
        yield MenuItem::linkToUrl('menu.sponsor', 'fa fa-euro-sign', 'https://github.com/sponsors/javiereguiluz')->setLinkTarget('_blank');
    }
}
