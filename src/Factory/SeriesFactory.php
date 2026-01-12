<?php

namespace App\Factory;

use App\Entity\Series;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Series>
 */
final class SeriesFactory extends PersistentObjectFactory
{
    private const SERIES_TITLES = [
        'Getting Started with Symfony',
        'Advanced PHP Techniques',
        'Building APIs from Scratch',
        'Docker for Developers',
        'Testing Best Practices',
        'Frontend Integration Guide',
        'Database Optimization',
        'Security Fundamentals',
    ];

    private static int $index = 0;

    public static function class(): string
    {
        return Series::class;
    }

    protected function defaults(): array|callable
    {
        $title = self::SERIES_TITLES[self::$index % \count(self::SERIES_TITLES)];
        ++self::$index;

        $slugger = new AsciiSlugger();

        return [
            'title' => $title,
            'slug' => $slugger->slug($title)->lower(),
            'description' => self::faker()->paragraphs(2, true),
            'coverImage' => null,
            'isComplete' => self::faker()->boolean(30),
            'author' => UserFactory::random(),
            'createdAt' => \DateTimeImmutable::createFromMutable(
                self::faker()->dateTimeBetween('-1 year', '-1 month')
            ),
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
