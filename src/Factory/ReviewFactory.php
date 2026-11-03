<?php

namespace App\Factory;

use App\Entity\Review;
use App\Enum\ProductStatus;
use App\Enum\ReviewStatus;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Review>
 */
final class ReviewFactory extends PersistentObjectFactory
{
    private const COMMON_REVIEWS = [
        'Arrived in perfect condition.',
        'Fast delivery and very careful packaging.',
        'Exactly as described in the product page.',
        'Good value for the price.',
        'It does the job. Nothing special, but no complaints.',
        'Delivery took a few days longer than expected, but the product is fine.',
    ];

    private const PLANT_REVIEWS = [
        'The plant looks healthy and has already grown new leaves.',
        'A beautiful plant that fits perfectly in my living room.',
        'Smaller than I expected, but healthy and growing well.',
        'Very easy to care for, even for a beginner like me.',
        'The care instructions in the box were really useful.',
        'My second one from this shop. Strong roots and great color.',
    ];

    private const SUPPLY_REVIEWS = [
        'Good quality material. My plants look happy with it.',
        'Nice finish and it looks great next to my plants.',
        'Solid and well made. I would buy it again.',
        'Works well for my indoor plants.',
        'The size is right for a medium plant.',
        'Simple and practical. It is what I needed.',
    ];

    public static function class(): string
    {
        return Review::class;
    }

    protected function defaults(): array|callable
    {
        $product = ProductFactory::random(['status' => ProductStatus::Published]);
        $specificReviews = $product->getFamily()->isPlantFamily() ? self::PLANT_REVIEWS : self::SUPPLY_REVIEWS;

        return [
            'content' => self::faker()->randomElement([...self::COMMON_REVIEWS, ...$specificReviews]),
            'rating' => self::faker()->randomElement([3, 4, 4, 5, 5, 5]),
            'status' => self::faker()->randomElement([
                ReviewStatus::Approved,
                ReviewStatus::Approved,
                ReviewStatus::Approved,
                ReviewStatus::Approved,
                ReviewStatus::Pending,
                ReviewStatus::Rejected,
                ReviewStatus::Spam,
            ]),
            'publishedAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTimeBetween('-1 year', 'now')),
            'customer' => CustomerFactory::random(),
            'product' => $product,
        ];
    }

    protected function initialize(): static
    {
        return $this;
    }

    public function pending(): static
    {
        return $this->with(['status' => ReviewStatus::Pending]);
    }
}
