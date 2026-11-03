<?php

namespace App\Factory;

use App\Entity\Product;
use App\Enum\ProductStatus;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Product>
 */
final class ProductFactory extends PersistentObjectFactory
{
    public const PLANTS = [
        ['name' => 'Monstera', 'botanicalName' => 'Monstera deliciosa', 'height' => 120.0, 'originCountry' => 'MX', 'price' => 3490, 'description' => 'A climbing plant with large split leaves. It grows well in bright, indirect light.'],
        ['name' => 'Snake plant', 'botanicalName' => 'Dracaena trifasciata', 'height' => 70.0, 'originCountry' => 'NG', 'price' => 2190, 'description' => 'Tall, stiff leaves with yellow edges. It accepts low light and needs little water.'],
        ['name' => 'Pothos', 'botanicalName' => 'Epipremnum aureum', 'height' => 40.0, 'originCountry' => 'PF', 'price' => 1290, 'description' => 'A trailing plant with heart-shaped leaves. It looks great on a shelf or in a hanging pot.'],
        ['name' => 'Peace lily', 'botanicalName' => 'Spathiphyllum wallisii', 'height' => 60.0, 'originCountry' => 'CO', 'price' => 1890, 'description' => 'Dark green leaves and white flowers. It likes shade and moist soil.'],
        ['name' => 'Fiddle-leaf fig', 'botanicalName' => 'Ficus lyrata', 'height' => 150.0, 'originCountry' => 'CM', 'price' => 5990, 'description' => 'A small indoor tree with large, violin-shaped leaves. Keep it close to a bright window.'],
        ['name' => 'Rubber plant', 'botanicalName' => 'Ficus elastica', 'height' => 130.0, 'originCountry' => 'IN', 'price' => 3990, 'description' => 'Thick, glossy leaves on a strong stem. Water it when the top of the soil is dry.'],
        ['name' => 'Spider plant', 'botanicalName' => 'Chlorophytum comosum', 'height' => 30.0, 'originCountry' => 'ZA', 'price' => 990, 'description' => 'Arching striped leaves and small baby plants that you can grow into new plants.'],
        ['name' => 'Aloe vera', 'botanicalName' => 'Aloe vera', 'height' => 40.0, 'originCountry' => 'OM', 'price' => 1490, 'description' => 'A succulent with thick leaves full of gel. It needs a lot of sun and little water.'],
        ['name' => 'Boston fern', 'botanicalName' => 'Nephrolepis exaltata', 'height' => 50.0, 'originCountry' => 'BR', 'price' => 1690, 'description' => 'Soft, feathery fronds that love humidity. A good choice for bathrooms with a window.'],
        ['name' => 'ZZ plant', 'botanicalName' => 'Zamioculcas zamiifolia', 'height' => 65.0, 'originCountry' => 'TZ', 'price' => 2790, 'description' => 'Shiny leaves on upright stems. It stores water in its roots, so it tolerates dry soil.'],
        ['name' => 'Calathea', 'botanicalName' => 'Goeppertia orbifolia', 'height' => 45.0, 'originCountry' => 'BO', 'price' => 2490, 'description' => 'Round leaves with silver stripes. It prefers filtered light and regular misting.'],
        ['name' => 'Jade plant', 'botanicalName' => 'Crassula ovata', 'height' => 35.0, 'originCountry' => 'ZA', 'price' => 1590, 'description' => 'A slow-growing succulent with thick branches and round leaves. It can live for many years.'],
        ['name' => 'Olive tree', 'botanicalName' => 'Olea europaea', 'height' => 140.0, 'originCountry' => 'ES', 'price' => 6990, 'description' => 'A classic Mediterranean tree with silver-green leaves, for sunny terraces and gardens.'],
        ['name' => 'Lavender', 'botanicalName' => 'Lavandula angustifolia', 'height' => 40.0, 'originCountry' => 'FR', 'price' => 890, 'description' => 'A fragrant outdoor plant with purple flowers in summer. It likes full sun and dry soil.'],
        ['name' => 'Rosemary', 'botanicalName' => 'Salvia rosmarinus', 'height' => 50.0, 'originCountry' => 'IT', 'price' => 790, 'description' => 'An aromatic herb for your kitchen or garden. It grows best in a sunny place.'],
    ];

    // 'family' isn't a product attribute: it tells the story which family to assign to each supply
    public const SUPPLIES = [
        ['name' => 'Universal potting soil', 'family' => FamilyFactory::SOIL_AND_FERTILIZER, 'price' => 690, 'description' => 'A balanced mix for most indoor and outdoor plants. Bag of 10 liters.'],
        ['name' => 'Orchid bark mix', 'family' => FamilyFactory::SOIL_AND_FERTILIZER, 'price' => 890, 'description' => 'Pine bark and perlite that let the roots of your orchids breathe. Bag of 5 liters.'],
        ['name' => 'Cactus soil', 'family' => FamilyFactory::SOIL_AND_FERTILIZER, 'price' => 750, 'description' => 'A sandy mix that drains fast, for cacti and succulents. Bag of 5 liters.'],
        ['name' => 'Organic plant fertilizer', 'family' => FamilyFactory::SOIL_AND_FERTILIZER, 'price' => 1190, 'description' => 'Slow-release pellets made from natural ingredients. Pack of 1 kilogram.'],
        ['name' => 'Liquid houseplant food', 'family' => FamilyFactory::SOIL_AND_FERTILIZER, 'price' => 990, 'description' => 'Add a few drops to your watering can every two weeks. Bottle of 500 milliliters.'],
        ['name' => 'Terracotta pot', 'family' => FamilyFactory::POTS_AND_DECORATION, 'price' => 1290, 'description' => 'A classic clay pot with a drainage hole and a saucer. Diameter of 20 centimeters.'],
        ['name' => 'Glazed ceramic pot', 'family' => FamilyFactory::POTS_AND_DECORATION, 'price' => 2490, 'description' => 'A ceramic pot with a smooth glaze for indoor plants. Diameter of 18 centimeters.'],
        ['name' => 'Hanging planter', 'family' => FamilyFactory::POTS_AND_DECORATION, 'price' => 1990, 'description' => 'A small pot with a cotton rope, for trailing plants. It includes the ceiling hook.'],
        ['name' => 'Woven plant basket', 'family' => FamilyFactory::POTS_AND_DECORATION, 'price' => 2990, 'description' => 'A natural seagrass basket that hides plastic nursery pots. It has two handles.'],
        ['name' => 'Brass plant mister', 'family' => FamilyFactory::POTS_AND_DECORATION, 'price' => 2290, 'description' => 'A small spray bottle that adds humidity around your plants and looks good on a shelf.'],
    ];

    private static int $plantIndex = 0;
    private static int $supplyIndex = 0;

    public static function class(): string
    {
        return Product::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'status' => ProductStatus::Published,
            'discount' => self::faker()->randomElement([0, 0, 0, 0, 0, 10, 15, 25]),
            'stock' => self::faker()->boolean(15) ? 0 : self::faker()->numberBetween(1, 120),
            'isFeatured' => self::faker()->boolean(15),
            'createdAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTimeBetween('-3 years', '-1 year')),
        ];
    }

    protected function initialize(): static
    {
        return $this;
    }

    // states pass a closure because Foundry evaluates it once per object: a plain
    // array would give the same item to all the objects of a many() collection
    public function plant(): static
    {
        return $this->with(static function (): array {
            $plant = self::PLANTS[self::$plantIndex % \count(self::PLANTS)];
            ++self::$plantIndex;

            return self::attributesFromListItem($plant);
        });
    }

    public function supply(): static
    {
        return $this->with(static function (): array {
            $supply = self::SUPPLIES[self::$supplyIndex % \count(self::SUPPLIES)];
            ++self::$supplyIndex;

            return self::attributesFromListItem($supply);
        });
    }

    public static function resetIndexes(): void
    {
        self::$plantIndex = 0;
        self::$supplyIndex = 0;
    }

    private static function attributesFromListItem(array $item): array
    {
        unset($item['family']);
        $item['slug'] = (new AsciiSlugger())->slug($item['name'])->lower()->toString();
        $item['image'] = $item['slug'].'.webp';

        return $item;
    }
}
