<?php

namespace App\Factory;

use Faker\Factory;
use Faker\Generator;

/**
 * The details of the countries used in the fixtures. Names and addresses are generated
 * with the Faker locale of each country, so customers look like they really live there.
 */
final class CountryData
{
    private const COUNTRIES = [
        'US' => ['fakerLocale' => 'en_US', 'language' => 'en', 'locale' => 'en_US', 'timezone' => 'America/New_York', 'callingCode' => '1', 'cities' => ['New York', 'Boston', 'Miami']],
        'GB' => ['fakerLocale' => 'en_GB', 'language' => 'en', 'locale' => 'en_GB', 'timezone' => 'Europe/London', 'callingCode' => '44', 'cities' => ['London', 'Manchester', 'Leeds']],
        'CA' => ['fakerLocale' => 'en_CA', 'language' => 'en', 'locale' => 'en_CA', 'timezone' => 'America/Toronto', 'callingCode' => '1', 'cities' => ['Toronto', 'Ottawa', 'Hamilton']],
        'ES' => ['fakerLocale' => 'es_ES', 'language' => 'es', 'locale' => 'es_ES', 'timezone' => 'Europe/Madrid', 'callingCode' => '34', 'cities' => ['Madrid', 'Barcelona', 'Valencia']],
        'AR' => ['fakerLocale' => 'es_AR', 'language' => 'es', 'locale' => 'es_AR', 'timezone' => 'America/Argentina/Buenos_Aires', 'callingCode' => '54', 'cities' => ['Buenos Aires', 'Córdoba', 'Rosario']],
        // Faker has no locale for Mexico and Colombia, so they use the closest one with their own postal code format
        'MX' => ['fakerLocale' => 'es_VE', 'postalCodeFormat' => '#####', 'language' => 'es', 'locale' => 'es_MX', 'timezone' => 'America/Mexico_City', 'callingCode' => '52', 'cities' => ['Mexico City', 'Guadalajara', 'Monterrey']],
        'CO' => ['fakerLocale' => 'es_VE', 'postalCodeFormat' => '######', 'language' => 'es', 'locale' => 'es_CO', 'timezone' => 'America/Bogota', 'callingCode' => '57', 'cities' => ['Bogotá', 'Medellín', 'Cali']],
        'VE' => ['fakerLocale' => 'es_VE', 'language' => 'es', 'locale' => 'es_VE', 'timezone' => 'America/Caracas', 'callingCode' => '58', 'cities' => ['Caracas', 'Maracaibo', 'Valencia']],
        'FR' => ['fakerLocale' => 'fr_FR', 'language' => 'fr', 'locale' => 'fr_FR', 'timezone' => 'Europe/Paris', 'callingCode' => '33', 'cities' => ['Paris', 'Lyon', 'Marseille']],
        'DE' => ['fakerLocale' => 'de_DE', 'language' => 'de', 'locale' => 'de_DE', 'timezone' => 'Europe/Berlin', 'callingCode' => '49', 'cities' => ['Berlin', 'Hamburg', 'Munich']],
        'IT' => ['fakerLocale' => 'it_IT', 'language' => 'it', 'locale' => 'it_IT', 'timezone' => 'Europe/Rome', 'callingCode' => '39', 'cities' => ['Rome', 'Milan', 'Turin']],
        'SE' => ['fakerLocale' => 'sv_SE', 'language' => 'sv', 'locale' => 'sv_SE', 'timezone' => 'Europe/Stockholm', 'callingCode' => '46', 'cities' => ['Stockholm', 'Gothenburg', 'Malmö']],
        'FI' => ['fakerLocale' => 'fi_FI', 'language' => 'fi', 'locale' => 'fi_FI', 'timezone' => 'Europe/Helsinki', 'callingCode' => '358', 'cities' => ['Helsinki', 'Espoo', 'Tampere']],
        'DK' => ['fakerLocale' => 'da_DK', 'language' => 'da', 'locale' => 'da_DK', 'timezone' => 'Europe/Copenhagen', 'callingCode' => '45', 'cities' => ['Copenhagen', 'Aarhus', 'Odense']],
        'BR' => ['fakerLocale' => 'pt_BR', 'language' => 'pt', 'locale' => 'pt_BR', 'timezone' => 'America/Sao_Paulo', 'callingCode' => '55', 'cities' => ['Sao Paulo', 'Rio de Janeiro', 'Curitiba']],
        'AU' => ['fakerLocale' => 'en_AU', 'language' => 'en', 'locale' => 'en_AU', 'timezone' => 'Australia/Sydney', 'callingCode' => '61', 'cities' => ['Sydney', 'Melbourne', 'Brisbane']],
        'IN' => ['fakerLocale' => 'en_IN', 'language' => 'en', 'locale' => 'en_IN', 'timezone' => 'Asia/Kolkata', 'callingCode' => '91', 'cities' => ['Mumbai', 'Delhi', 'Pune']],
        'NG' => ['fakerLocale' => 'en_NG', 'language' => 'en', 'locale' => 'en_NG', 'timezone' => 'Africa/Lagos', 'callingCode' => '234', 'cities' => ['Lagos', 'Abuja', 'Ibadan']],
    ];

    /** @var array<string, Generator> */
    private static array $fakers = [];

    /**
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_keys(self::COUNTRIES);
    }

    /**
     * @return array{fakerLocale: string, postalCodeFormat?: string, language: string, locale: string, timezone: string, callingCode: string, cities: list<string>}
     */
    public static function get(string $country): array
    {
        return self::COUNTRIES[$country];
    }

    public static function faker(string $country): Generator
    {
        return self::$fakers[$country] ??= Factory::create(self::COUNTRIES[$country]['fakerLocale']);
    }
}
