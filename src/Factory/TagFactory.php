<?php

namespace App\Factory;

use App\Entity\Tag;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Tag>
 */
final class TagFactory extends PersistentObjectFactory
{
    /**
     * Cross-category tags that work well with diverse content.
     */
    private const TAGS = [
        // Content type
        'Tutorial',
        'Guide',
        'Tips',
        'Review',
        'Opinion',
        'News',
        'Interview',
        // Audience level
        'Beginner',
        'Advanced',
        // Attributes
        'Trending',
        'Budget',
        'Premium',
        'Quick Read',
        'In-Depth',
        'DIY',
        'Research',
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
