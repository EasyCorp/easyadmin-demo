<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Enum\DemoUser;
use App\Enum\ProductStatus;
use App\Enum\ReviewStatus;
use App\Repository\CustomerRepository;
use App\Repository\FamilyRepository;
use App\Repository\ProductRepository;
use App\Repository\ReviewRepository;
use App\Security\DemoUserAuthenticator;
use App\Twig\AppExtension;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Menu\ControllerMenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\Font;
use EasyCorp\Bundle\EasyAdminBundle\Config\Theme;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

#[AdminDashboard(routePath: '/{_locale}/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    private const LATEST_ITEMS = 5;

    public function __construct(
        #[Autowire('%kernel.enabled_locales%')] private array $enabledLocales,
        private ProductRepository $productRepository,
        private FamilyRepository $familyRepository,
        private ReviewRepository $reviewRepository,
        private CustomerRepository $customerRepository,
        private AppExtension $appExtension,
    ) {
    }

    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig', [
            'demo_users' => DemoUser::cases(),
            'csrf_token_id' => DemoUserAuthenticator::CSRF_TOKEN_ID,
            'published_products' => $this->productRepository->count(['status' => ProductStatus::Published]),
            'units_in_stock' => $this->productRepository->sumStock(),
            'pending_reviews' => $this->reviewRepository->count(['status' => ReviewStatus::Pending]),
            'confirmed_customers' => $this->customerRepository->count(['isConfirmed' => true]),
            'latest_pending_reviews' => $this->reviewRepository->findLatestPending(self::LATEST_ITEMS),
            'latest_products' => $this->productRepository->findLatestByStatus($this->isGranted('ROLE_EDITOR') ? ProductStatus::Draft : ProductStatus::Published, self::LATEST_ITEMS),
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('<span class="shop-logo"><img src="/favicon.png" alt="" width="24" height="24">Plant Shop</span>')
            ->setFaviconPath('favicon.png')
            ->setLocales($this->enabledLocales)
            ->setTheme(Theme::new()
                ->primaryColor('emerald')
                ->font(base: Font::IbmPlexSans)
            );
    }

    public function configureAssets(): Assets
    {
        return Assets::new()
            ->useCustomIconSet('lucide')
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

    public function configureActions(): Actions
    {
        // anonymous visitors and readers can browse everything but can't change anything;
        // each CRUD controller can override these permissions (e.g. to use a security voter)
        return parent::configureActions()
            ->setPermissions([
                Action::NEW => 'ROLE_EDITOR',
                Action::EDIT => 'ROLE_EDITOR',
                Action::DELETE => 'ROLE_ADMIN',
                Action::BATCH_DELETE => 'ROLE_ADMIN',
            ]);
    }

    public function configureUserMenu(UserInterface $user): UserMenu
    {
        $userMenu = parent::configureUserMenu($user);

        if ($user instanceof User) {
            if (null === $user->getAvatar()) {
                $userMenu->setGravatarEmail($user->getEmail());
            } else {
                $userMenu->setAvatarUrl($this->appExtension->avatarUrl($user->getAvatar(), $user->getEmail()));
            }

            if ($this->isGranted('ROLE_EDITOR')) {
                $managedByUser = ['filters' => ['manager' => ['comparison' => '=', 'value' => $user->getId()]]];
                $userMenu->addMenuItems([
                    MenuItem::linkToUrl('menu.my_plants', 'leaf', $this->generateUrl('admin_plant_index', $managedByUser)),
                    MenuItem::linkToUrl('menu.my_supplies', 'shovel', $this->generateUrl('admin_supply_index', $managedByUser)),
                ]);
            }
        }

        return $userMenu;
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linktoDashboard('menu.dashboard', 'house');
        yield MenuItem::linkTo(FeatureTourController::class, 'menu.feature_tour', 'map');

        yield MenuItem::section('menu.catalog');
        yield $this->withDraftsBadge(MenuItem::linkTo(PlantCrudController::class, 'entity.plants', 'leaf'), true);
        yield $this->withDraftsBadge(MenuItem::linkTo(SupplyCrudController::class, 'entity.supplies', 'shovel'), false);
        yield MenuItem::linkTo(FamilyCrudController::class, 'entity.families', 'folder-tree');
        yield MenuItem::linkTo(TagCrudController::class, 'entity.tags', 'tags');

        yield MenuItem::section('menu.sales');
        yield MenuItem::linkTo(OrderCrudController::class, 'entity.orders', 'shopping-cart');

        $unconfirmedCount = $this->customerRepository->count(['isConfirmed' => false]);
        $customersMenuItem = MenuItem::linkTo(CustomerCrudController::class, 'entity.customers', 'contact');
        if ($unconfirmedCount > 0) {
            $customersMenuItem->setBadge($unconfirmedCount, 'info');
        }
        yield $customersMenuItem;

        $pendingReviewsCount = $this->reviewRepository->count(['status' => ReviewStatus::Pending]);
        $pendingReviewsMenuItem = MenuItem::linkTo(PendingReviewCrudController::class, 'menu.pending_moderation');
        $reviewsMenuItem = MenuItem::subMenu('entity.reviews', 'star')->setSubItems([
            MenuItem::linkTo(ReviewCrudController::class, 'menu.all_reviews'),
            $pendingReviewsMenuItem,
        ]);
        if ($pendingReviewsCount > 0) {
            $pendingReviewsMenuItem->setBadge($pendingReviewsCount, 'danger');
        }
        yield $reviewsMenuItem;

        yield MenuItem::section('menu.purchasing');
        yield MenuItem::linkTo(SupplierCrudController::class, 'entity.suppliers', 'truck');

        yield MenuItem::section('menu.administration')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkTo(UserCrudController::class, 'entity.staff', 'users')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkTo(FixturesController::class, 'menu.fixtures_data', 'database')
            ->setAction('regenerateFixtures')
            ->setPermission('ROLE_ADMIN');

        yield MenuItem::section('menu.resources');
        yield MenuItem::linkToRoute('menu.ui_kit', 'swatch-book', 'admin_ea_ui_kit_index');
        yield MenuItem::linkToUrl('menu.docs', 'book-open', 'https://symfony.com/bundles/EasyAdminBundle')->setLinkTarget('_blank');
        yield MenuItem::linkToUrl('menu.pro', 'gem', 'https://easycorp.io/pro')->setLinkTarget('_blank');
        yield MenuItem::linkToUrl('menu.demo', 'wand-sparkles', 'https://github.com/EasyCorp/easyadmin-demo')->setLinkTarget('_blank');
        yield MenuItem::linkToUrl('menu.sponsor', 'euro', 'https://github.com/sponsors/javiereguiluz')->setLinkTarget('_blank');
    }

    private function withDraftsBadge(ControllerMenuItem $menuItem, bool $isPlantFamily): ControllerMenuItem
    {
        if (!$this->isGranted('ROLE_EDITOR')) {
            return $menuItem;
        }

        $draftCount = $this->productRepository->count([
            'status' => ProductStatus::Draft,
            'family' => $this->familyRepository->findBy(['isPlantFamily' => $isPlantFamily]),
        ]);
        if ($draftCount > 0) {
            $menuItem->setBadge($draftCount);
        }

        return $menuItem;
    }
}
