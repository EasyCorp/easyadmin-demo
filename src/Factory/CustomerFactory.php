<?php

namespace App\Factory;

use App\Entity\Customer;
use App\Enum\CustomerSource;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Customer>
 */
final class CustomerFactory extends PersistentObjectFactory
{
    private const NOTES = [
        'Prefers delivery in the morning.',
        'Asked for plant care tips with each order.',
        'Regular customer of our physical store.',
        'Please call before delivery.',
    ];

    // one customer per country; names and countries are chosen by hand so they match the
    // person shown in each avatar
    private const CUSTOMERS = [
        ['name' => 'Claire Dubois', 'country' => 'FR', 'avatar' => 'customer-1.webp'],
        ['name' => 'Walter Hughes', 'country' => 'GB', 'avatar' => 'customer-2.webp'],
        ['name' => 'Lucía Romero', 'country' => 'ES', 'avatar' => 'customer-3.webp'],
        ['name' => 'Marcus Reed', 'country' => 'US', 'avatar' => 'customer-4.webp'],
        ['name' => 'Helen Fischer', 'country' => 'DE', 'avatar' => 'customer-5.webp'],
        ['name' => 'Diego Santos', 'country' => 'AR', 'avatar' => 'customer-6.webp'],
        ['name' => 'Emma Larsson', 'country' => 'SE', 'avatar' => 'customer-7.webp'],
        ['name' => 'Paul Bennett', 'country' => 'CA', 'avatar' => 'customer-8.webp'],
        ['name' => 'Amara Okafor', 'country' => 'NG', 'avatar' => 'customer-9.webp'],
        ['name' => 'Liam Murphy', 'country' => 'AU', 'avatar' => 'customer-10.webp'],
        ['name' => 'Sofia Ricci', 'country' => 'IT', 'avatar' => 'customer-11.webp'],
        ['name' => 'Rafael Costa', 'country' => 'BR', 'avatar' => 'customer-12.webp'],
        ['name' => 'Carlos Mendoza', 'country' => 'MX', 'avatar' => 'customer-13.webp'],
        ['name' => 'Camila Restrepo', 'country' => 'CO', 'avatar' => 'customer-14.webp'],
        ['name' => 'Daniela Rojas', 'country' => 'VE', 'avatar' => 'customer-15.webp'],
        ['name' => 'Mikko Korhonen', 'country' => 'FI', 'avatar' => 'customer-16.webp'],
        ['name' => 'Freja Madsen', 'country' => 'DK', 'avatar' => 'customer-17.webp'],
        ['name' => 'Arjun Nair', 'country' => 'IN', 'avatar' => 'customer-18.webp'],
    ];

    public static function class(): string
    {
        return Customer::class;
    }

    /**
     * @return list<Customer>
     */
    public static function createAll(): array
    {
        return array_map(static fn (array $customer): Customer => self::createOne([
            ...self::attributesForCountry($customer['country']),
            ...$customer,
            'email' => self::emailFromName($customer['name']),
        ]), self::CUSTOMERS);
    }

    protected function defaults(): array|callable
    {
        $country = self::faker()->randomElement(CountryData::codes());
        $faker = CountryData::faker($country);
        $name = $faker->firstName().' '.$faker->lastName();

        return [
            ...self::attributesForCountry($country),
            'name' => $name,
            'email' => self::emailFromName($name.' '.self::faker()->unique()->numberBetween(1, 999)),
            'isConfirmed' => self::faker()->boolean(70),
            'acceptsNewsletter' => self::faker()->boolean(40),
            'source' => self::faker()->randomElement(CustomerSource::cases()),
            'notes' => self::faker()->boolean(20) ? self::faker()->randomElement(self::NOTES) : null,
            'ipAddress' => self::faker()->boolean(60) ? self::faker()->ipv4() : null,
            'createdAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTimeBetween('-3 years', '-1 year')),
        ];
    }

    protected function initialize(): static
    {
        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    private static function attributesForCountry(string $country): array
    {
        $data = CountryData::get($country);

        return [
            'country' => $country,
            'language' => $data['language'],
            'locale' => $data['locale'],
            'timezone' => $data['timezone'],
            'phone' => self::faker()->boolean(70) ? sprintf('+%s%s', $data['callingCode'], self::faker()->numerify('#########')) : null,
            'address' => self::faker()->boolean(85) ? AddressFactory::new()->inCountry($country) : null,
        ];
    }

    private static function emailFromName(string $name): string
    {
        return new AsciiSlugger()->slug($name, '.')->lower().'@example.com';
    }
}
