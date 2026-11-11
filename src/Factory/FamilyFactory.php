<?php

namespace App\Factory;

use App\Entity\Family;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Family>
 */
final class FamilyFactory extends PersistentObjectFactory
{
    public const PLANTS = 'Plants';
    public const SOIL_AND_FERTILIZER = 'Soil and fertilizer';
    public const POTS_AND_DECORATION = 'Pots and decoration';

    public const FAMILIES = [
        [
            'name' => self::PLANTS,
            'description' => 'Indoor and outdoor plants, from small herbs to young trees.',
            'isPlantFamily' => true,
        ],
        [
            'name' => self::SOIL_AND_FERTILIZER,
            'description' => 'Potting mixes and plant food to keep your plants healthy.',
            'isPlantFamily' => false,
        ],
        [
            'name' => self::POTS_AND_DECORATION,
            'description' => 'Pots, planters and accessories for your home and garden.',
            'isPlantFamily' => false,
        ],
    ];

    private static int $position = 0;

    public static function class(): string
    {
        return Family::class;
    }

    protected function defaults(): array|callable
    {
        $family = self::FAMILIES[self::$position % \count(self::FAMILIES)];
        ++self::$position;

        $slug = (new AsciiSlugger())->slug($family['name'])->lower()->toString();

        return [
            ...$family,
            'slug' => $slug,
            'cover' => $slug.'.webp',
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
