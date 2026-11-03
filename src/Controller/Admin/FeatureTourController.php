<?php

namespace App\Controller\Admin;

use App\Admin\SourceCode\FeatureTourSourceCode;
use App\Admin\SourceCode\SourceCodeReader;
use App\Enum\ProductStatus;
use App\Repository\CustomerRepository;
use App\Repository\FamilyRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

#[AdminRoute('/feature-tour', name: 'feature_tour')]
class FeatureTourController extends AbstractController
{
    private const DOCS_URL = 'https://symfony.com/bundles/EasyAdminBundle/current/';

    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly FamilyRepository $familyRepository,
        private readonly CustomerRepository $customerRepository,
        private readonly OrderRepository $orderRepository,
        private readonly SourceCodeReader $sourceCodeReader,
    ) {
    }

    #[AdminRoute('/', name: 'index')]
    public function __invoke(): Response
    {
        $plant = $this->productRepository->findOneBy([
            'status' => ProductStatus::Published,
            'family' => $this->familyRepository->findBy(['isPlantFamily' => true]),
        ]);
        $plantDetail = null === $plant ? ['admin_plant_index'] : ['admin_plant_detail', ['entityId' => $plant->getId()]];

        $customer = $this->customerRepository->findOneBy([]);
        $customerDetail = null === $customer ? ['admin_customer_index'] : ['admin_customer_detail', ['entityId' => $customer->getId()]];

        $order = $this->orderRepository->findOneBy([]);
        $orderPrint = null === $order ? ['admin_order_index'] : ['admin_order_print', ['entityId' => $order->getId()]];

        $groups = [
            'fields' => [
                ['custom_field', ['admin_family_index'], 'fields', 'icon' => 'gauge'],
                ['field_templates', ['admin_plant_index'], 'fields', 'icon' => 'layout-template'],
                ['images', ['admin_family_index'], 'fields', 'icon' => 'image-up'],
                ['all_fields', ['admin_ea_ui_kit_form_fields'], 'design', 'icon' => 'list-checks'],
                ['form_layout', $plantDetail, 'fields', 'icon' => 'columns-2'],
                ['tabs', $customerDetail, 'fields', 'icon' => 'panels-top-left'],
                ['embedded_form', ['admin_customer_new'], 'fields', 'ROLE_EDITOR', 'icon' => 'square-stack'],
                ['collection_crud_form', ['admin_order_new'], 'fields', 'ROLE_EDITOR', 'icon' => 'list-plus'],
            ],
            'filters' => [
                ['custom_filter', ['admin_plant_index'], 'filters', 'icon' => 'funnel-plus'],
                ['builtin_filters', ['admin_customer_index'], 'filters', 'icon' => 'list-filter'],
            ],
            'actions' => [
                ['action_group', $plantDetail, 'actions', 'icon' => 'group'],
                ['batch_actions', ['admin_review_index'], 'actions', 'icon' => 'check-check'],
                ['confirmation_closure', ['admin_order_index'], 'actions', 'icon' => 'message-circle-question-mark'],
                ['global_action', ['admin_tag_index'], 'actions', 'icon' => 'globe'],
                ['csv_export', ['admin_order_index'], 'actions', 'icon' => 'file-spreadsheet'],
                ['custom_page', ['admin_feature_tour_index'], 'actions', 'icon' => 'file-plus'],
                ['print_page', $orderPrint, 'actions', 'icon' => 'printer'],
            ],
            'security' => [
                ['demo_users', ['admin'], 'security', 'icon' => 'log-in'],
                ['action_permissions', ['admin_family_index'], 'security', 'icon' => 'shield-check'],
                ['voter', ['admin_plant_index'], 'security', 'icon' => 'vote'],
                ['field_permissions', ['admin_customer_index'], 'security', 'icon' => 'eye-off'],
                ['impersonation', ['admin_user_index'], 'security', 'ROLE_ADMIN', 'icon' => 'venetian-mask'],
            ],
            'design' => [
                ['theme', ['admin'], 'design', 'icon' => 'palette'],
                ['card_listing', ['admin_family_index'], 'design', 'icon' => 'layout-grid'],
                ['submenus', ['admin_review_index'], 'dashboards', 'icon' => 'list-tree'],
                ['twig_components', ['admin_customer_index'], 'components', 'icon' => 'blocks'],
                ['badge_variants', ['admin_plant_index'], 'fields/ChoiceField', 'icon' => 'badge-check'],
                ['ui_kit', ['admin_ea_ui_kit_index'], 'design', 'icon' => 'component'],
            ],
            'logic' => [
                ['two_crud_controllers', ['admin_supply_index'], 'crud', 'icon' => 'copy'],
                ['moderation_queue', ['admin_pending_review_index'], 'crud', 'icon' => 'message-square-warning'],
                ['events', ['admin_plant_index'], 'events', 'icon' => 'zap'],
                ['query_builder', ['admin_plant_index'], 'crud', 'icon' => 'database-search'],
                ['translations', ['admin'], 'dashboards', 'icon' => 'languages'],
            ],
        ];

        return $this->render('admin/feature_tour.html.twig', [
            'groups' => $groups,
            'docs_url' => self::DOCS_URL,
        ]);
    }

    #[AdminRoute('/{feature}/code', name: 'code', options: ['requirements' => ['feature' => '[a-z_]+']])]
    public function code(string $feature): Response
    {
        try {
            $files = FeatureTourSourceCode::forFeature($feature);
        } catch (\InvalidArgumentException $exception) {
            throw $this->createNotFoundException($exception->getMessage(), $exception);
        }

        return $this->render('admin/_source_code_files.html.twig', [
            'files' => array_map($this->sourceCodeReader->read(...), $files),
        ]);
    }
}
