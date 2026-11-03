<?php

namespace App\Factory;

use App\Entity\OrderLine;
use App\Enum\ProductStatus;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<OrderLine>
 */
final class OrderLineFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return OrderLine::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'order' => OrderFactory::random(),
            'product' => ProductFactory::random(['status' => ProductStatus::Published]),
            'quantity' => self::faker()->numberBetween(1, 4),
        ];
    }

    protected function initialize(): static
    {
        // a default based on the attributes can't be set in defaults(), because
        // the product that it depends on can be overridden when creating the line
        return $this->afterInstantiate(static function (OrderLine $line): void {
            if (null === $line->getUnitPrice()) {
                $line->setUnitPrice($line->getProduct()->getPrice());
            }
        });
    }
}
