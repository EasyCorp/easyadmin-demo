<?php

namespace App\Factory;

use App\Entity\Order;
use App\Enum\OrderStatus;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Order>
 */
final class OrderFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Order::class;
    }

    protected function defaults(): array|callable
    {
        $status = self::faker()->randomElement([
            OrderStatus::Pending,
            OrderStatus::Paid,
            OrderStatus::Paid,
            OrderStatus::Shipped,
            OrderStatus::Shipped,
            OrderStatus::Delivered,
            OrderStatus::Delivered,
            OrderStatus::Cancelled,
        ]);

        // old orders that are still pending would look like a bug in the shop
        $placedAt = self::faker()->dateTimeBetween(OrderStatus::Pending === $status ? '-2 weeks' : '-1 year', 'now');

        return [
            'number' => self::faker()->unique()->numerify('ORD-#####'),
            'status' => $status,
            'placedAt' => \DateTimeImmutable::createFromMutable($placedAt),
            'shippedOn' => \in_array($status, [OrderStatus::Shipped, OrderStatus::Delivered], true)
                ? \DateTimeImmutable::createFromMutable(self::faker()->dateTimeBetween($placedAt, 'now'))
                : null,
            'customer' => CustomerFactory::random(),
        ];
    }

    protected function initialize(): static
    {
        return $this;
    }
}
