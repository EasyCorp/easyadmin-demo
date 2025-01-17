<?php

namespace App\Controller\Admin;

use App\Entity\Comment;
use App\Entity\FormFieldReference;
use App\Entity\Post;
use App\Entity\Tag;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin')]
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
        yield MenuItem::linktoDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('Users', 'fa fa-users', User::class);
        yield MenuItem::linkToCrud('Blog Posts', 'fa fa-file-text-o', Post::class);
        yield MenuItem::linkToCrud('Comments', 'far fa-comments', Comment::class);
        yield MenuItem::linkToCrud('Tags', 'fas fa-tags', Tag::class);

        yield MenuItem::section('Resources');
        yield MenuItem::linkToCrud('Form Field Reference', 'fa-solid fa-table-cells', FormFieldReference::class)->setAction(Action::NEW);
        yield MenuItem::linkToRoute('Fixtures data', 'fa-solid fa-database', 'admin_regenerate_fixtures');

        yield MenuItem::section('Links');
        yield MenuItem::linkToUrl('EasyAdmin Docs', 'fas fa-book', 'https://symfony.com/doc/current/bundles/EasyAdminBundle/index.html')->setLinkTarget('_blank');
        yield MenuItem::linkToUrl('EasyAdmin Demo', 'fas fa-magic', 'https://github.com/EasyCorp/easyadmin-demo')->setLinkTarget('_blank');
        yield MenuItem::linkToUrl('Sponsor EasyAdmin', 'fa fa-euro-sign', 'https://github.com/sponsors/javiereguiluz')->setLinkTarget('_blank');
    }
}
