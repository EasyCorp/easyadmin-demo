<?php

namespace App\Tests;

use App\Entity\User;
use App\Enum\DemoUser;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Smoke test to ensure all pages load without 500 errors.
 */
class SmokeTest extends WebTestCase
{
    use AnonymousClientTrait;

    #[DataProvider('publicUrlProvider')]
    public function testPublicPages(string $url, array $expectedCodes): void
    {
        $client = $this->createAnonymousClient();
        $client->request('GET', $url);

        $this->assertNoServerError($client, $url, $expectedCodes);
    }

    public static function publicUrlProvider(): iterable
    {
        // homepage
        yield 'homepage' => ['/', [302]];
        yield 'homepage_en' => ['/en/', [200, 302]];
        yield 'homepage_es' => ['/es/', [200, 302]];

        // admin dashboard
        yield 'admin_dashboard_en' => ['/en/admin', [200]];
        yield 'admin_dashboard_es' => ['/es/admin', [200]];

        // admin index pages (publicly accessible in this demo)
        yield 'admin_plant_index' => ['/en/admin/plant', [200]];
        yield 'admin_supply_index' => ['/en/admin/supply', [200]];
        yield 'admin_family_index' => ['/en/admin/family', [200]];
        yield 'admin_tag_index' => ['/en/admin/tag', [200]];
        yield 'admin_supplier_index' => ['/en/admin/supplier', [200]];
        yield 'admin_customer_index' => ['/en/admin/customer', [200]];
        yield 'admin_order_index' => ['/en/admin/order', [200]];
        yield 'admin_review_index' => ['/en/admin/review', [200]];
        yield 'admin_pending_review_index' => ['/en/admin/pending-review', [200]];
        yield 'admin_user_index' => ['/en/admin/user', [403]];

        // new pages (anonymous visitors can't create content)
        yield 'admin_plant_new' => ['/en/admin/plant/new', [403]];
        yield 'admin_supply_new' => ['/en/admin/supply/new', [403]];
        yield 'admin_family_new' => ['/en/admin/family/new', [403]];
        yield 'admin_tag_new' => ['/en/admin/tag/new', [403]];
        yield 'admin_supplier_new' => ['/en/admin/supplier/new', [403]];
        yield 'admin_customer_new' => ['/en/admin/customer/new', [403]];
        yield 'admin_order_new' => ['/en/admin/order/new', [403]];
        yield 'admin_review_new' => ['/en/admin/review/new', [403]];
        yield 'admin_pending_review_new' => ['/en/admin/pending-review/new', [403]];
        yield 'admin_user_new' => ['/en/admin/user/new', [403]];
    }

    // anonymous visitors get a 403 on these pages, so this is what really renders the forms
    #[DataProvider('newPageUrlProvider')]
    public function testNewPagesAsAdmin(string $url, int $expectedCode): void
    {
        $client = $this->createClientLoggedInAsAdmin();
        $client->request('GET', $url);

        $this->assertNoServerError($client, $url, [$expectedCode]);
    }

    public static function newPageUrlProvider(): iterable
    {
        yield 'plant_new' => ['/en/admin/plant/new', 200];
        yield 'supply_new' => ['/en/admin/supply/new', 200];
        yield 'family_new' => ['/en/admin/family/new', 200];
        yield 'tag_new' => ['/en/admin/tag/new', 200];
        yield 'supplier_new' => ['/en/admin/supplier/new', 200];
        yield 'customer_new' => ['/en/admin/customer/new', 200];
        yield 'order_new' => ['/en/admin/order/new', 200];
        yield 'review_new' => ['/en/admin/review/new', 200];
        // the moderation queue disables this action: reviews are created from the main listing
        yield 'pending_review_new' => ['/en/admin/pending-review/new', 403];
        yield 'user_new' => ['/en/admin/user/new', 200];
    }

    #[DataProvider('entityQueryProvider')]
    public function testEntityDetailPages(string $entityIdQuery, string $urlPrefix): void
    {
        $client = $this->createClientLoggedInAsAdmin();

        $url = sprintf('%s/%d', $urlPrefix, $this->fetchEntityId($entityIdQuery));
        $client->request('GET', $url);

        $this->assertNoServerError($client, $url, [200]);
    }

    #[DataProvider('entityQueryProvider')]
    public function testEntityEditPages(string $entityIdQuery, string $urlPrefix): void
    {
        $client = $this->createClientLoggedInAsAdmin();

        $url = sprintf('%s/%d/edit', $urlPrefix, $this->fetchEntityId($entityIdQuery));
        $client->request('GET', $url);

        $this->assertNoServerError($client, $url, [200]);
    }

    public static function entityQueryProvider(): iterable
    {
        yield 'plant' => ['SELECT p.id FROM App\Entity\Product p JOIN p.family f WHERE f.isPlantFamily = true', '/en/admin/plant'];
        yield 'supply' => ['SELECT p.id FROM App\Entity\Product p JOIN p.family f WHERE f.isPlantFamily = false', '/en/admin/supply'];
        yield 'family' => ['SELECT e.id FROM App\Entity\Family e', '/en/admin/family'];
        yield 'tag' => ['SELECT e.id FROM App\Entity\Tag e', '/en/admin/tag'];
        yield 'supplier' => ['SELECT e.id FROM App\Entity\Supplier e', '/en/admin/supplier'];
        yield 'customer' => ['SELECT e.id FROM App\Entity\Customer e', '/en/admin/customer'];
        yield 'order' => ['SELECT e.id FROM App\Entity\Order e', '/en/admin/order'];
        yield 'review' => ['SELECT e.id FROM App\Entity\Review e', '/en/admin/review'];
        yield 'pending_review' => ["SELECT e.id FROM App\Entity\Review e WHERE e.status = 'pending'", '/en/admin/pending-review'];
        yield 'user' => ['SELECT e.id FROM App\Entity\User e', '/en/admin/user'];
    }

    private function createClientLoggedInAsAdmin(): KernelBrowser
    {
        $client = static::createClient();
        $client->loginUser($this->getEntityManager()->getRepository(User::class)->findOneBy(['email' => DemoUser::Admin->email()]));

        return $client;
    }

    private function fetchEntityId(string $entityIdQuery): int
    {
        $entityId = $this->getEntityManager()->createQuery($entityIdQuery)->setMaxResults(1)->getOneOrNullResult(AbstractQuery::HYDRATE_SINGLE_SCALAR);
        $this->assertNotNull($entityId, sprintf('The query "%s" found nothing. Regenerate the fixtures.', $entityIdQuery));

        return (int) $entityId;
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    private function assertNoServerError(KernelBrowser $client, string $url, array $acceptedCodes): void
    {
        $statusCode = $client->getResponse()->getStatusCode();
        $this->assertLessThan(
            500,
            $statusCode,
            sprintf('URL "%s" returned server error %d', $url, $statusCode)
        );
        $this->assertContains(
            $statusCode,
            $acceptedCodes,
            sprintf(
                'URL "%s" returned %d. Expected one of: %s',
                $url,
                $statusCode,
                implode(', ', $acceptedCodes)
            )
        );
    }
}
