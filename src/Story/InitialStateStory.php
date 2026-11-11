<?php

namespace App\Story;

use App\Entity\Family;
use App\Entity\User;
use App\Enum\DemoUser;
use App\Enum\ProductStatus;
use App\Factory\CustomerFactory;
use App\Factory\FamilyFactory;
use App\Factory\OrderFactory;
use App\Factory\OrderLineFactory;
use App\Factory\ProductFactory;
use App\Factory\ReviewFactory;
use App\Factory\SupplierFactory;
use App\Factory\TagFactory;
use App\Factory\UserFactory;
use Zenstruck\Foundry\Attribute\AsFixture;
use Zenstruck\Foundry\Story;

#[AsFixture('initial_state')]
final class InitialStateStory extends Story
{
    // tags are chosen by hand because random ones give contradictions, like a plant
    // with both "Low light" and "Bright light", or a Monstera that is "Handmade"
    private const PRODUCT_TAGS = [
        'Monstera' => ['Bright light', 'Fast growth', 'Humidity loving', 'Indoor'],
        'Snake plant' => ['Low light', 'Easy care', 'Air purifying', 'Drought tolerant', 'Indoor'],
        'Pothos' => ['Low light', 'Easy care', 'Fast growth', 'Air purifying', 'Indoor'],
        'Peace lily' => ['Low light', 'Air purifying', 'Humidity loving', 'Indoor'],
        'Fiddle-leaf fig' => ['Bright light', 'Humidity loving', 'Indoor'],
        'Rubber plant' => ['Bright light', 'Easy care', 'Air purifying', 'Indoor'],
        'Spider plant' => ['Easy care', 'Fast growth', 'Air purifying', 'Indoor'],
        'Aloe vera' => ['Bright light', 'Easy care', 'Slow growth', 'Drought tolerant', 'Indoor'],
        'Boston fern' => ['Air purifying', 'Humidity loving', 'Indoor'],
        'ZZ plant' => ['Low light', 'Easy care', 'Slow growth', 'Drought tolerant', 'Indoor'],
        'Calathea' => ['Low light', 'Humidity loving', 'Indoor'],
        'Jade plant' => ['Bright light', 'Easy care', 'Slow growth', 'Drought tolerant', 'Indoor'],
        'Olive tree' => ['Bright light', 'Slow growth', 'Drought tolerant', 'Outdoor'],
        'Lavender' => ['Bright light', 'Drought tolerant', 'Outdoor'],
        'Rosemary' => ['Bright light', 'Easy care', 'Drought tolerant', 'Outdoor'],
        'Universal potting soil' => ['Indoor', 'Outdoor'],
        'Orchid bark mix' => ['Organic', 'Indoor'],
        'Cactus soil' => ['Indoor', 'Outdoor'],
        'Organic plant fertilizer' => ['Organic', 'Indoor', 'Outdoor'],
        'Liquid houseplant food' => ['Indoor'],
        'Terracotta pot' => ['Handmade', 'Outdoor'],
        'Glazed ceramic pot' => ['Handmade', 'Indoor'],
        'Hanging planter' => ['Handmade', 'Indoor'],
        'Woven plant basket' => ['Handmade', 'Indoor'],
        'Brass plant mister' => ['Indoor'],
    ];

    public function build(): void
    {
        FamilyFactory::resetPosition();
        TagFactory::resetIndex();
        SupplierFactory::resetIndex();
        ProductFactory::resetIndexes();

        $demoUsers = [];
        foreach (DemoUser::cases() as $demoUser) {
            $demoUsers[$demoUser->value] = UserFactory::createOne([
                'fullName' => $demoUser->fullName(),
                'email' => $demoUser->email(),
                'roles' => [$demoUser->role()],
                'isVerified' => true,
                'avatar' => $demoUser->value.'.webp',
            ]);
        }
        $extraEditors = UserFactory::new()->editor()->sequence([
            ['fullName' => 'Daniel Vega', 'email' => 'daniel.vega@example.com', 'avatar' => 'staff-1.webp'],
            ['fullName' => 'Nora Keller', 'email' => 'nora.keller@example.com', 'avatar' => 'staff-2.webp'],
        ])->create();
        UserFactory::new(['roles' => ['ROLE_USER']])->sequence([
            ['fullName' => 'Samuel Brooks', 'email' => 'samuel.brooks@example.com', 'avatar' => 'staff-3.webp'],
            ['fullName' => 'Ruth Walsh', 'email' => 'ruth.walsh@example.com', 'avatar' => 'staff-4.webp'],
            ['fullName' => 'Oliver Grant', 'email' => 'oliver.grant@example.com', 'avatar' => 'staff-5.webp'],
        ])->create();

        FamilyFactory::createMany(3);
        TagFactory::createMany(12);
        SupplierFactory::createMany(6);

        $demoEditor = $demoUsers[DemoUser::Editor->value];
        $managers = [$demoUsers[DemoUser::Admin->value], $demoEditor, ...$extraEditors];
        $assignments = $this->assignManagersAndStatuses($demoEditor, $managers);
        $plantAssignments = \array_slice($assignments, 0, \count(ProductFactory::PLANTS));
        $supplyAssignments = \array_slice($assignments, \count(ProductFactory::PLANTS));

        $plantFamily = FamilyFactory::find(['name' => FamilyFactory::PLANTS]);
        ProductFactory::new()->plant()->sequence(array_map(
            fn (array $assignment, array $plant): array => $this->productAttributes($assignment, $plantFamily, $plant['name']),
            $plantAssignments,
            ProductFactory::PLANTS,
        ))->create();
        ProductFactory::new()->supply()->sequence(array_map(
            fn (array $assignment, array $supply): array => $this->productAttributes($assignment, FamilyFactory::find(['name' => $supply['family']]), $supply['name']),
            $supplyAssignments,
            ProductFactory::SUPPLIES,
        ))->create();

        $customers = CustomerFactory::createAll();

        foreach (OrderFactory::createMany(40) as $order) {
            foreach (ProductFactory::randomSet(random_int(1, 4), ['status' => ProductStatus::Published]) as $product) {
                OrderLineFactory::createOne(['order' => $order, 'product' => $product]);
            }
        }

        ReviewFactory::createMany(52);
        // guarantees that the moderation queue and the dashboard always have something to
        // show; these reviews are the latest ones so the dashboard lists them first
        ReviewFactory::new()->pending()->with(static fn (): array => [
            'customer' => $customers[array_rand($customers)],
            'publishedAt' => new \DateTimeImmutable(sprintf('-%d hours', random_int(1, 48))),
        ])->many(8)->create();
    }

    /**
     * @param array{manager: User, status: ProductStatus} $assignment
     *
     * @return array<string, mixed>
     */
    private function productAttributes(array $assignment, Family $family, string $productName): array
    {
        return [
            ...$assignment,
            'family' => $family,
            'tags' => TagFactory::findBy(['name' => self::PRODUCT_TAGS[$productName]]),
            'supplier' => SupplierFactory::random(),
        ];
    }

    /**
     * About 10% of the products are drafts and 5% are archived. The demo editor always
     * manages some published products and some drafts, so visitors can compare what
     * editors can do with their own products and with the products of other people.
     *
     * @param User[] $managers
     *
     * @return list<array{manager: User, status: ProductStatus}>
     */
    private function assignManagersAndStatuses(User $demoEditor, array $managers): array
    {
        $randomManager = static fn (): User => $managers[array_rand($managers)];

        $assignments = [
            ['manager' => $demoEditor, 'status' => ProductStatus::Draft],
            ['manager' => $demoEditor, 'status' => ProductStatus::Draft],
            ['manager' => $randomManager(), 'status' => ProductStatus::Draft],
            ['manager' => $randomManager(), 'status' => ProductStatus::Archived],
        ];
        for ($i = 0; $i < 4; ++$i) {
            $assignments[] = ['manager' => $demoEditor, 'status' => ProductStatus::Published];
        }
        while (\count($assignments) < \count(ProductFactory::PLANTS) + \count(ProductFactory::SUPPLIES)) {
            $assignments[] = ['manager' => $randomManager(), 'status' => ProductStatus::Published];
        }

        shuffle($assignments);

        return $assignments;
    }
}
