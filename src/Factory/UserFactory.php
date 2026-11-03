<?php

namespace App\Factory;

use App\Entity\User;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<User>
 */
final class UserFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return User::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'fullName' => self::faker()->name(),
            'email' => self::faker()->unique()->safeEmail(),
            'roles' => self::faker()->randomElement([
                ['ROLE_USER'],
                ['ROLE_EDITOR'],
            ]),
            'isVerified' => self::faker()->boolean(70),
            'lastLoginAt' => self::faker()->boolean(80)
                ? \DateTimeImmutable::createFromMutable(self::faker()->dateTimeBetween('-1 month', 'now'))
                : null,
        ];
    }

    protected function initialize(): static
    {
        return $this;
    }

    public function editor(): static
    {
        return $this->with([
            'roles' => ['ROLE_EDITOR'],
            'isVerified' => true,
        ]);
    }
}
