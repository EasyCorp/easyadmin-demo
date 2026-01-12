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
        $firstName = self::faker()->firstName();
        $lastName = self::faker()->lastName();

        return [
            'fullName' => $firstName.' '.$lastName,
            'email' => strtolower($firstName).'.'.strtolower($lastName).'@example.com',
            'username' => strtolower($firstName).self::faker()->numberBetween(1, 999),
            'password' => 'password123', // In real app, this would be hashed
            'roles' => self::faker()->randomElement([
                ['ROLE_USER'],
                ['ROLE_USER'],
                ['ROLE_USER'],
                ['ROLE_EDITOR'],
                ['ROLE_EDITOR'],
                ['ROLE_ADMIN'],
            ]),
            'isVerified' => self::faker()->boolean(70),
            'bio' => self::faker()->boolean(60) ? self::faker()->paragraph(2) : null,
            'website' => self::faker()->boolean(40) ? self::faker()->url() : null,
            'twitterHandle' => self::faker()->boolean(30) ? self::faker()->userName() : null,
            'lastLoginAt' => self::faker()->boolean(80)
                ? \DateTimeImmutable::createFromMutable(self::faker()->dateTimeBetween('-1 month', 'now'))
                : null,
        ];
    }

    protected function initialize(): static
    {
        return $this;
    }

    public function admin(): static
    {
        return $this->with([
            'roles' => ['ROLE_ADMIN'],
            'isVerified' => true,
        ]);
    }

    public function editor(): static
    {
        return $this->with([
            'roles' => ['ROLE_EDITOR'],
            'isVerified' => true,
        ]);
    }

    public function verified(): static
    {
        return $this->with([
            'isVerified' => true,
        ]);
    }
}
