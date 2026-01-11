<?php

namespace App\Factory;

use App\Entity\Subscriber;
use App\Enum\SubscriberSource;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Subscriber>
 */
final class SubscriberFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Subscriber::class;
    }

    protected function defaults(): array|callable
    {
        $isConfirmed = self::faker()->boolean(70);
        $subscribedAt = \DateTimeImmutable::createFromMutable(
            self::faker()->dateTimeBetween('-1 year', 'now')
        );

        return [
            'email' => self::faker()->unique()->safeEmail(),
            'name' => self::faker()->boolean(80) ? self::faker()->name() : null,
            'subscribedAt' => $subscribedAt,
            'isConfirmed' => $isConfirmed,
            'confirmedAt' => $isConfirmed
                ? $subscribedAt->modify('+'.self::faker()->numberBetween(1, 48).' hours')
                : null,
            'source' => self::faker()->randomElement(SubscriberSource::cases()),
            'unsubscribedAt' => self::faker()->boolean(10)
                ? \DateTimeImmutable::createFromMutable(self::faker()->dateTimeBetween('-3 months', 'now'))
                : null,
            'locale' => self::faker()->randomElement(['en', 'es']),
            'notes' => self::faker()->boolean(20) ? self::faker()->sentence() : null,
            'ipAddress' => self::faker()->boolean(60) ? self::faker()->ipv4() : null,
        ];
    }

    protected function initialize(): static
    {
        return $this;
    }

    public function confirmed(): static
    {
        return $this->with([
            'isConfirmed' => true,
            'confirmedAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTimeBetween('-6 months', 'now')),
        ]);
    }

    public function pending(): static
    {
        return $this->with([
            'isConfirmed' => false,
            'confirmedAt' => null,
        ]);
    }
}
