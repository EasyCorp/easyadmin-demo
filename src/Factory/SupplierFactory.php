<?php

namespace App\Factory;

use App\Entity\Supplier;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Supplier>
 */
final class SupplierFactory extends PersistentObjectFactory
{
    public const SUPPLIERS = [
        ['name' => 'Green Valley Nursery', 'country' => 'NL', 'contactName' => 'Sanne de Vries', 'phone' => '+31201234567'],
        ['name' => 'Terra Supplies', 'country' => 'IT', 'contactName' => 'Giulia Conti', 'phone' => '+39061234567'],
        ['name' => 'Northwood Growers', 'country' => 'DE', 'contactName' => 'Jonas Becker', 'phone' => '+49301234567'],
        ['name' => 'Cerâmicas do Sol', 'country' => 'PT', 'contactName' => 'Inês Carvalho', 'phone' => '+351211234567'],
        ['name' => 'Les Jardins de la Prairie', 'country' => 'FR', 'contactName' => 'Luc Lambert', 'phone' => '+33123456789'],
        ['name' => 'Viveros El Olivar', 'country' => 'ES', 'contactName' => 'Marta Gil', 'phone' => '+34911234567'],
    ];

    private static int $index = 0;

    public static function class(): string
    {
        return Supplier::class;
    }

    protected function defaults(): array|callable
    {
        $supplier = self::SUPPLIERS[self::$index % \count(self::SUPPLIERS)];
        ++self::$index;

        $slug = (new AsciiSlugger())->slug($supplier['name'])->lower()->toString();

        return [
            ...$supplier,
            'email' => sprintf('sales@%s.example.com', $slug),
            'website' => sprintf('https://%s.example.com', $slug),
        ];
    }

    protected function initialize(): static
    {
        return $this;
    }

    public static function resetIndex(): void
    {
        self::$index = 0;
    }
}
