<?php

namespace App\Factory;

use App\Entity\Category;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Category>
 */
final class CategoryFactory extends PersistentObjectFactory
{
    private const CATEGORIES = [
        ['name' => 'Technology', 'icon' => 'fa-microchip', 'color' => '#3498db'],
        ['name' => 'Lifestyle', 'icon' => 'fa-heart', 'color' => '#e74c3c'],
        ['name' => 'Travel', 'icon' => 'fa-plane', 'color' => '#2ecc71'],
        ['name' => 'Food & Recipes', 'icon' => 'fa-utensils', 'color' => '#f39c12'],
        ['name' => 'Business', 'icon' => 'fa-briefcase', 'color' => '#9b59b6'],
        ['name' => 'Health', 'icon' => 'fa-heartbeat', 'color' => '#1abc9c'],
        ['name' => 'Entertainment', 'icon' => 'fa-film', 'color' => '#e91e63'],
        ['name' => 'Science', 'icon' => 'fa-flask', 'color' => '#00bcd4'],
    ];

    private static int $position = 0;

    public static function class(): string
    {
        return Category::class;
    }

    protected function defaults(): array|callable
    {
        $category = self::CATEGORIES[self::$position % \count(self::CATEGORIES)];
        ++self::$position;

        $slugger = new AsciiSlugger();

        return [
            'name' => $category['name'],
            'slug' => $slugger->slug($category['name'])->lower(),
            'description' => self::faker()->paragraph(),
            'color' => $category['color'],
            'icon' => $category['icon'],
            'position' => self::$position,
        ];
    }

    protected function initialize(): static
    {
        return $this;
    }

    public static function resetPosition(): void
    {
        self::$position = 0;
    }
}
