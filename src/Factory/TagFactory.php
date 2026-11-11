<?php

namespace App\Factory;

use App\Entity\Tag;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Tag>
 */
final class TagFactory extends PersistentObjectFactory
{
    public const TAGS = [
        'Low light',
        'Bright light',
        'Easy care',
        'Slow growth',
        'Fast growth',
        'Air purifying',
        'Drought tolerant',
        'Humidity loving',
        'Indoor',
        'Outdoor',
        'Organic',
        'Handmade',
    ];

    private static int $index = 0;

    public static function class(): string
    {
        return Tag::class;
    }

    protected function defaults(): array|callable
    {
        $name = self::TAGS[self::$index % \count(self::TAGS)];
        ++self::$index;

        return [
            'name' => $name,
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
