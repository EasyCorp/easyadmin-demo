<?php

namespace App\Factory;

use App\Entity\Address;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Address>
 */
final class AddressFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Address::class;
    }

    protected function defaults(): array|callable
    {
        return self::attributesForCountry(self::faker()->randomElement(CountryData::codes()));
    }

    protected function initialize(): static
    {
        return $this;
    }

    public function inCountry(string $country): static
    {
        return $this->with(self::attributesForCountry($country));
    }

    /**
     * @return array{street: string, postalCode: string, city: string, country: string}
     */
    private static function attributesForCountry(string $country): array
    {
        $faker = CountryData::faker($country);
        $data = CountryData::get($country);

        return [
            'street' => str_replace("\n", ' ', $faker->streetAddress()),
            'postalCode' => isset($data['postalCodeFormat']) ? $faker->numerify($data['postalCodeFormat']) : $faker->postcode(),
            'city' => self::faker()->randomElement($data['cities']),
            'country' => $country,
        ];
    }
}
