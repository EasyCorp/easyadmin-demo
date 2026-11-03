<?php

namespace App\Admin\SourceCode;

/**
 * The files that implement each feature listed in the feature tour, and the code
 * of those files that is relevant for that feature.
 */
final class FeatureTourSourceCode
{
    /**
     * @return list<SourceCodeFile>
     */
    public static function forFeature(string $feature): array
    {
        return match ($feature) {
            'custom_field' => [
                SourceCodeFile::new('src/Admin/Field/FamilyStockField.php')->markMethods('new', 'showPercentage', 'showCount'),
                SourceCodeFile::new('templates/admin/field/family_stock.html.twig')->markLines('field.customOptions.get('),
                SourceCodeFile::new('src/Controller/Admin/FamilyCrudController.php')->markStatements('FamilyStockField::new('),
            ],
            'field_templates' => [
                SourceCodeFile::new('src/Controller/Admin/AbstractProductCrudController.php')->markLines('->setTemplatePath('),
                SourceCodeFile::new('templates/admin/product/_manager_card.html.twig'),
            ],
            'images' => [
                SourceCodeFile::new('src/Controller/Admin/FamilyCrudController.php')->markStatements("ImageField::new('cover'"),
                SourceCodeFile::new('src/Controller/Admin/AbstractProductCrudController.php')->markStatements("ImageField::new('image'"),
            ],
            'all_fields' => [
                SourceCodeFile::new('config/services.yaml')->markLines('easyadmin.ui_kit.enabled'),
            ],
            'form_layout' => [
                SourceCodeFile::new('src/Controller/Admin/AbstractProductCrudController.php')->markLines('FormField::addColumn(', 'FormField::addFieldset('),
                SourceCodeFile::new('src/Controller/Admin/PlantCrudController.php')->markLines('FormField::addFieldset('),
            ],
            'tabs' => [
                SourceCodeFile::new('src/Controller/Admin/CustomerCrudController.php')->markStatements('FormField::addTab('),
            ],
            'embedded_form' => [
                SourceCodeFile::new('src/Controller/Admin/CustomerCrudController.php')->markStatements("AssociationField::new('address')"),
                SourceCodeFile::new('src/Controller/Admin/AddressCrudController.php')->markMethods('configureFields'),
            ],
            'collection_crud_form' => [
                SourceCodeFile::new('src/Controller/Admin/OrderCrudController.php')->markStatements("CollectionField::new('lines')"),
                SourceCodeFile::new('src/Controller/Admin/OrderLineCrudController.php')->markMethods('configureFields'),
            ],
            'custom_filter' => [
                SourceCodeFile::new('src/Admin/Filter/SupplierWithMinProductsFilter.php')->markMethods('new', 'apply'),
                SourceCodeFile::new('src/Controller/Admin/AbstractProductCrudController.php')->markLines('SupplierWithMinProductsFilter::new('),
            ],
            'builtin_filters' => [
                SourceCodeFile::new('src/Controller/Admin/CustomerCrudController.php')->markMethods('configureFilters'),
            ],
            'action_group' => [
                SourceCodeFile::new('src/Controller/Admin/AbstractProductCrudController.php')->markStatements('ActionGroup::new(')->markLines('$statusActionGroup)'),
            ],
            'batch_actions' => [
                SourceCodeFile::new('src/Controller/Admin/ReviewCrudController.php')
                    ->markStatements("Action::new('batch")
                    ->markLines('->add(Crud::PAGE_INDEX, $batch')
                    ->markMethods('batchApprove'),
            ],
            'confirmation_closure' => [
                SourceCodeFile::new('src/Controller/Admin/OrderCrudController.php')->markStatements("Action::new('cancel'"),
                SourceCodeFile::new('src/Controller/Admin/AbstractProductCrudController.php')->markStatements("Action::new('archive'"),
            ],
            'global_action' => [
                SourceCodeFile::new('src/Admin/Action/SourceCodeAction.php')->markMethods('create'),
                SourceCodeFile::new('src/Controller/Admin/TagCrudController.php')->markLines('SourceCodeAction::create()'),
                SourceCodeFile::new('templates/bundles/EasyAdminBundle/crud/index.html.twig')->markBlocks('{% if global_actions', '{% endif %}'),
            ],
            'csv_export' => [
                SourceCodeFile::new('src/Controller/Admin/OrderCrudController.php')
                    ->markStatements("Action::new('exportCsv'")
                    ->markMethods('exportCsv'),
            ],
            'custom_page' => [
                SourceCodeFile::new('src/Controller/Admin/FeatureTourController.php')->markClass()->markMethods('__invoke'),
                SourceCodeFile::new('templates/admin/feature_tour.html.twig')->markLines('{% extends'),
                SourceCodeFile::new('src/Controller/Admin/DashboardController.php')->markLines('FeatureTourController::class'),
            ],
            'print_page' => [
                SourceCodeFile::new('src/Controller/Admin/OrderCrudController.php')
                    ->markStatements("Action::new('print'")
                    ->markMethods('printOrder'),
                SourceCodeFile::new('templates/admin/order/print.html.twig')->markLines('{% extends'),
            ],
            'demo_users' => [
                SourceCodeFile::new('src/Security/DemoUserAuthenticator.php')->markMethods('supports', 'authenticate'),
                SourceCodeFile::new('src/EventSubscriber/DemoAutoLoginSubscriber.php')->markMethods('logInAnonymousVisitors'),
                SourceCodeFile::new('src/Controller/Admin/DashboardController.php')->markMethods('configureUserMenu'),
                SourceCodeFile::new('templates/admin/dashboard.html.twig')->markBlocks('<form method="post"', '</form>'),
                SourceCodeFile::new('config/packages/security.yaml')->markLines('custom_authenticators:', 'DemoUserAuthenticator'),
            ],
            'action_permissions' => [
                SourceCodeFile::new('src/Controller/Admin/DashboardController.php')->markMethods('configureActions'),
                SourceCodeFile::new('src/Controller/Admin/OrderCrudController.php')->markStatements("->setPermission('markPaid'"),
            ],
            'voter' => [
                SourceCodeFile::new('src/Security/ProductVoter.php')->markMethods('supports', 'voteOnAttribute'),
                SourceCodeFile::new('src/Controller/Admin/AbstractProductCrudController.php')->markLines('ProductVoter::'),
            ],
            'field_permissions' => [
                SourceCodeFile::new('src/Controller/Admin/CustomerCrudController.php')->markLines("->setPermission('ROLE_ADMIN')"),
            ],
            'impersonation' => [
                SourceCodeFile::new('src/Controller/Admin/UserCrudController.php')->markMethods('configureActions'),
                SourceCodeFile::new('config/packages/security.yaml')->markLines('ROLE_ALLOWED_TO_SWITCH', 'switch_user: true'),
            ],
            'theme' => [
                SourceCodeFile::new('src/Controller/Admin/DashboardController.php')->markMethods('configureDashboard', 'configureAssets'),
            ],
            'card_listing' => [
                SourceCodeFile::new('src/Controller/Admin/FamilyCrudController.php')->markLines('->overrideTemplate('),
                SourceCodeFile::new('templates/admin/family/index.html.twig'),
                SourceCodeFile::new('assets/styles/admin.css')->markBlocks('/* card listing', '}'),
            ],
            'submenus' => [
                SourceCodeFile::new('src/Controller/Admin/DashboardController.php')->markStatements('MenuItem::subMenu(')->markLines('yield $reviewsMenuItem;'),
            ],
            'twig_components' => [
                SourceCodeFile::new('templates/admin/customer/_status.html.twig')->markLines('<twig:ea:'),
                SourceCodeFile::new('templates/admin/product/_manager_card.html.twig')->markLines('<twig:ea:'),
            ],
            'badge_variants' => [
                SourceCodeFile::new('src/Controller/Admin/AbstractProductCrudController.php')->markLines('->setBadgeVariant('),
                SourceCodeFile::new('src/Controller/Admin/UserCrudController.php')->markLines('->setBadgeVariant('),
            ],
            'ui_kit' => [
                SourceCodeFile::new('config/services.yaml')->markLines('easyadmin.ui_kit.enabled'),
                SourceCodeFile::new('src/Controller/Admin/DashboardController.php')->markLines("'admin_ea_ui_kit_index'"),
            ],
            'two_crud_controllers' => [
                SourceCodeFile::new('src/Controller/Admin/AbstractProductCrudController.php')->markClass()->markMethods('getEntityFqcn', 'createIndexQueryBuilder')->markLines('abstract protected function managesPlants()'),
                SourceCodeFile::new('src/Controller/Admin/PlantCrudController.php')->markClass()->markMethods('managesPlants', 'getBotanicalFields'),
                SourceCodeFile::new('src/Controller/Admin/SupplyCrudController.php')->markClass()->markMethods('managesPlants'),
            ],
            'moderation_queue' => [
                SourceCodeFile::new('src/Controller/Admin/PendingReviewCrudController.php')->markClass()->markMethods('configureCrud', 'createIndexQueryBuilder'),
                SourceCodeFile::new('src/Controller/Admin/DashboardController.php')->markLines('PendingReviewCrudController::class'),
            ],
            'events' => [
                SourceCodeFile::new('src/EventSubscriber/EasyAdminSubscriber.php')->markMethods('getSubscribedEvents', 'onBeforeEntityPersisted', 'onBeforeEntityUpdated'),
            ],
            'query_builder' => [
                SourceCodeFile::new('src/Controller/Admin/AbstractProductCrudController.php')->markMethods('createIndexQueryBuilder'),
            ],
            'translations' => [
                SourceCodeFile::new('src/Controller/Admin/DashboardController.php')->markLines('->setLocales('),
                SourceCodeFile::new('config/packages/framework.yaml')->markLines('enabled_locales:'),
                SourceCodeFile::new('templates/admin/feature_tour.html.twig')->markLines('|trans'),
            ],
            default => throw new \InvalidArgumentException(sprintf('The feature tour does not include a feature called "%s".', $feature)),
        };
    }
}
