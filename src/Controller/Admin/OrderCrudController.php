<?php

namespace App\Controller\Admin;

use App\Admin\Action\SourceCodeAction;
use App\Admin\EntityPageTitle;
use App\Entity\Order;
use App\Enum\OrderStatus;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\TextAlign;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CurrencyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

class OrderCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OrderRepository $orderRepository,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Order::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('order.label')
            ->setPageTitle(Crud::PAGE_DETAIL, fn (Order $order): TranslatableMessage => EntityPageTitle::detail('order', [
                'number' => (string) $order->getNumber(),
                'total' => (new \NumberFormatter($this->getContext()->getI18n()->getLocale(), \NumberFormatter::CURRENCY))->formatCurrency($order->getTotal() / 100, $order->getCurrency()),
            ]))
            ->setPageTitle(Crud::PAGE_EDIT, static fn (Order $order): TranslatableMessage => EntityPageTitle::edit('order', ['number' => (string) $order->getNumber()]))
            ->setEntityLabelInPlural('order.label_plural')
            ->setDefaultSort(['placedAt' => 'DESC'])
            ->setSearchFields(['number', 'customer.name', 'customer.email'])
            ->setDefaultRowAction(Action::DETAIL)
            ->hideNullValues();
    }

    public function createEntity(string $entityFqcn): Order
    {
        // the number is unique, so a random one that is already taken is discarded
        do {
            $number = sprintf('ORD-%s-%04d', date('Ymd'), random_int(0, 9999));
        } while (0 < $this->orderRepository->count(['number' => $number]));

        $order = new Order();
        $order->setNumber($number);

        return $order;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->onlyOnIndex();

        if (Crud::PAGE_DETAIL === $pageName) {
            yield FormField::addColumn('col-lg-4');
        }

        yield FormField::addFieldset('order.fieldset.details', 'shopping-cart');

        $number = TextField::new('number')
            ->setLabel('order.number')
            ->setDisabled();
        if (Crud::PAGE_INDEX === $pageName) {
            $number->setTemplatePath('admin/order/_number.html.twig');
        }
        yield $number;

        yield AssociationField::new('customer')
            ->setLabel('order.customer')
            ->setSortProperty('name')
            ->autocomplete();

        $status = ChoiceField::new('status')
            ->setLabel('order.status')
            ->renderAsBadges(OrderStatus::badges());
        if (Crud::PAGE_INDEX === $pageName) {
            $status->setTemplatePath('admin/order/_status.html.twig');
        }
        yield $status;

        yield MoneyField::new('total')
            ->setLabel('order.total')
            ->setCurrencyPropertyPath('currency')
            ->setSortable(false)
            ->hideOnForm();

        $placedAt = DateTimeField::new('placedAt')
            ->setLabel('order.placedAt');
        if (Crud::PAGE_INDEX === $pageName) {
            $placedAt->setFormat('d MMM, HH:mm')->setTextAlign(TextAlign::RIGHT);
        }
        yield $placedAt;

        yield DateField::new('shippedOn')
            ->setLabel('order.shippedOn')
            ->hideOnIndex();

        yield CurrencyField::new('currency')
            ->setLabel('order.currency')
            ->onlyOnDetail();

        if (Crud::PAGE_DETAIL === $pageName) {
            yield FormField::addColumn('col-lg-8');
        }

        yield FormField::addFieldset('order.fieldset.lines', 'package');

        yield CollectionField::new('lines')
            ->setLabel(Crud::PAGE_DETAIL === $pageName ? false : 'order.lines')
            ->useEntryCrudForm(OrderLineCrudController::class)
            ->setEntryIsComplex()
            ->renderExpanded()
            ->setColumns(12)
            ->setTemplatePath('admin/order/_lines.html.twig')
            ->hideOnIndex();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('status', 'order.status')->setTranslatableChoices(OrderStatus::cases()))
            ->add(EntityFilter::new('customer', 'order.customer'))
            ->add(DateTimeFilter::new('placedAt', 'order.placedAt'))
            ->add(DateTimeFilter::new('shippedOn', 'order.shippedOn'));
    }

    public function configureActions(Actions $actions): Actions
    {
        // custom actions must declare their permissions too because they don't inherit the ones of the
        // dashboard. They hide the buttons and also protect the URLs of the actions, so their methods don't check them again
        $actions
            ->setPermission('markPaid', 'ROLE_EDITOR')
            ->setPermission('markShipped', 'ROLE_EDITOR')
            ->setPermission('markDelivered', 'ROLE_EDITOR')
            ->setPermission('cancel', 'ROLE_EDITOR')
            ->setPermission('exportCsv', 'ROLE_EDITOR')
            ->setPermission('print', 'PUBLIC_ACCESS');
        $markPaidAction = Action::new('markPaid', 'action.mark_paid', 'credit-card')
            ->linkToCrudAction('markOrderAsPaid')
            ->renderAsForm()
            ->displayIf(static fn (Order $order): bool => $order->isPending())
            ->asSuccessAction();

        $markShippedAction = Action::new('markShipped', 'action.mark_shipped', 'truck')
            ->linkToCrudAction('markOrderAsShipped')
            ->renderAsForm()
            ->displayIf(static fn (Order $order): bool => $order->isPaid())
            ->asSuccessAction();

        $markDeliveredAction = Action::new('markDelivered', 'action.mark_delivered', 'package-check')
            ->linkToCrudAction('markOrderAsDelivered')
            ->renderAsForm()
            ->displayIf(static fn (Order $order): bool => $order->isShipped())
            ->asSuccessAction();

        $cancelAction = Action::new('cancel', 'action.cancel_order', 'ban')
            ->linkToCrudAction('cancelOrder')
            ->renderAsForm()
            ->displayIf(static fn (Order $order): bool => $order->isPending() || $order->isPaid())
            ->asDangerAction()
            ->askConfirmation(static fn (Order $order): TranslatableMessage => new TranslatableMessage(
                $order->isPaid() ? 'order.confirm.cancel_paid' : 'order.confirm.cancel',
                ['number' => (string) $order->getNumber()],
            ));
        $exportCsvAction = Action::new('exportCsv', 'action.export_csv', 'download')
            ->linkToCrudAction('exportCsv')
            ->createAsGlobalAction();
        $printAction = Action::new('print', 'action.print', 'printer')
            ->linkToCrudAction('printOrder');

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, SourceCodeAction::create())
            ->add(Crud::PAGE_INDEX, $exportCsvAction)
            ->add(Crud::PAGE_INDEX, $cancelAction)
            ->add(Crud::PAGE_INDEX, $markDeliveredAction)
            ->add(Crud::PAGE_INDEX, $markShippedAction)
            ->add(Crud::PAGE_INDEX, $markPaidAction)
            ->add(Crud::PAGE_DETAIL, $printAction)
            ->add(Crud::PAGE_DETAIL, $cancelAction)
            ->add(Crud::PAGE_DETAIL, $markDeliveredAction)
            ->add(Crud::PAGE_DETAIL, $markShippedAction)
            ->add(Crud::PAGE_DETAIL, $markPaidAction);
    }

    #[AdminRoute('/{entityId:order.id}/mark-as-paid', options: ['methods' => ['POST']])]
    public function markOrderAsPaid(Order $order): Response
    {
        if (!$order->isPending()) {
            return $this->redirectWithInvalidStatusMessage();
        }

        $order->markAsPaid();

        $this->entityManager->flush();

        $this->addFlash('success', 'order.flash.paid');

        return $this->redirectToRoute('admin_order_index');
    }

    #[AdminRoute('/{entityId:order.id}/mark-as-shipped', options: ['methods' => ['POST']])]
    public function markOrderAsShipped(Order $order): Response
    {
        if (!$order->isPaid()) {
            return $this->redirectWithInvalidStatusMessage();
        }

        $order->markAsShipped();

        $this->entityManager->flush();

        $this->addFlash('success', 'order.flash.shipped');

        return $this->redirectToRoute('admin_order_index');
    }

    #[AdminRoute('/{entityId:order.id}/mark-as-delivered', options: ['methods' => ['POST']])]
    public function markOrderAsDelivered(Order $order): Response
    {
        if (!$order->isShipped()) {
            return $this->redirectWithInvalidStatusMessage();
        }

        $order->markAsDelivered();

        $this->entityManager->flush();

        $this->addFlash('success', 'order.flash.delivered');

        return $this->redirectToRoute('admin_order_index');
    }

    #[AdminRoute('/{entityId:order.id}/cancel', options: ['methods' => ['POST']])]
    public function cancelOrder(Order $order): Response
    {
        if (!$order->isPending() && !$order->isPaid()) {
            return $this->redirectWithInvalidStatusMessage();
        }

        $order->cancel();

        $this->entityManager->flush();

        $this->addFlash('success', 'order.flash.cancelled');

        return $this->redirectToRoute('admin_order_index');
    }

    #[AdminRoute('/export', name: 'export')]
    public function exportCsv(): StreamedResponse
    {
        // translated here because Symfony resets the translator locale when the
        // main request finishes, which happens before the response content is streamed
        $headers = array_map($this->translator->trans(...), [
            'order.number',
            'order.status',
            'order.placedAt',
            'order.shippedOn',
            'order.customer',
            'customer.email',
            'order.currency',
            'order.total',
        ]);
        $orders = $this->orderRepository->findBy([], ['placedAt' => 'DESC']);

        $response = new StreamedResponse(static function () use ($headers, $orders): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, $headers, escape: '');

            foreach ($orders as $order) {
                fputcsv($output, [
                    $order->getNumber(),
                    $order->getStatus()->value,
                    $order->getPlacedAt()->format('Y-m-d H:i:s'),
                    $order->getShippedOn()?->format('Y-m-d'),
                    self::escapeCsvFormula($order->getCustomer()->getName()),
                    self::escapeCsvFormula($order->getCustomer()->getEmail()),
                    $order->getCurrency(),
                    number_format($order->getTotal() / 100, 2, '.', ''),
                ], escape: '');
            }

            fclose($output);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            sprintf('orders-%s.csv', date('Y-m-d'))
        ));

        return $response;
    }

    /**
     * Unlike the other actions, this one renders the EasyAdmin layout, whose language selector
     * reads the "entityId" request attribute. A mapped "{entityId:order.id}" placeholder
     * removes that attribute, and then the URLs of the selector can't be generated.
     */
    #[AdminRoute('/{entityId}/print', name: 'print')]
    public function printOrder(#[MapEntity(id: 'entityId')] Order $order): Response
    {
        return $this->render('admin/order/print.html.twig', [
            'order' => $order,
        ]);
    }

    /**
     * Spreadsheet applications run cells that start with these characters as formulas,
     * and customer data can be written by anyone who buys in the shop.
     */
    private static function escapeCsvFormula(?string $value): ?string
    {
        return null !== $value && '' !== $value && str_contains("=+-@\t\r", $value[0]) ? "'".$value : $value;
    }

    private function redirectWithInvalidStatusMessage(): Response
    {
        $this->addFlash('warning', 'order.flash.invalid_status');

        return $this->redirectToRoute('admin_order_index');
    }
}
