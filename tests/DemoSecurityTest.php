<?php

namespace App\Tests;

use App\Entity\Customer;
use App\Entity\Family;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\User;
use App\Enum\DemoUser;
use App\Enum\ProductStatus;
use App\Enum\ReviewStatus;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class DemoSecurityTest extends WebTestCase
{
    use AnonymousClientTrait;

    public function testAnonymousVisitorsCanBrowseButNotChangeContent(): void
    {
        $client = $this->createAnonymousClient();

        $client->request('GET', '/en/admin/plant');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('.action-new');

        $client->request('GET', '/en/admin/plant/new');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testEditorsCanCreateProductsAndReviews(): void
    {
        $client = static::createClient();
        $client->loginUser($this->findDemoUser(DemoUser::Editor));

        $client->request('GET', '/en/admin/plant/new');
        $this->assertResponseIsSuccessful();

        $client->request('GET', '/en/admin/review/new');
        $this->assertResponseIsSuccessful();
    }

    public function testAnonymousVisitorsOnlySeePublishedProducts(): void
    {
        $client = $this->createAnonymousClient();
        $draft = $this->findPlant(['status' => ProductStatus::Draft]);

        $client->request('GET', sprintf('/en/admin/plant/%d', $draft->getId()));
        $this->assertResponseStatusCodeSame(403);

        $crawler = $client->request('GET', '/en/admin/plant?query='.urlencode((string) $draft->getName()));
        $this->assertStringNotContainsString(sprintf('data-id="%d"', $draft->getId()), $crawler->html());
    }

    public function testDashboardOnlyListsDraftsForEditors(): void
    {
        $client = $this->createAnonymousClient();

        $crawler = $client->request('GET', '/en/admin');
        $this->assertResponseIsSuccessful();
        $this->assertCount(0, $crawler->filter('#latest-products h2:contains("drafts")'));
        $this->assertStringNotContainsString('demo.', $crawler->filter('.content-body')->text(), 'The dashboard contains untranslated messages.');

        $client->loginUser($this->findDemoUser(DemoUser::Editor));
        $crawler = $client->request('GET', '/en/admin');
        $this->assertCount(1, $crawler->filter('#latest-products h2:contains("drafts")'));
    }

    public function testAnonymousVisitorsCannotRunCustomActions(): void
    {
        $client = $this->createAnonymousClient();
        $product = $this->findPlant(['status' => ProductStatus::Published]);

        $client->request('POST', sprintf('/en/admin/plant/%d/archive', $product->getId()));
        $this->assertResponseStatusCodeSame(403);

        $this->assertSame(ProductStatus::Published, $this->findPlant(['id' => $product->getId()])->getStatus());
    }

    public function testDemoUsersLogInWithOneClick(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/en/admin');
        $form = $crawler->filter('form.demo-user input[value="editor"]')->closest('form')->form();
        $client->submit($form);

        $this->assertResponseRedirects('/en/admin');
        $this->assertSame('editor', $this->getLoggedInUsername($client));
    }

    public function testVisitorsAreLoggedInAsAdminsUntilTheyLogOut(): void
    {
        $client = static::createClient();
        $this->assertSame('admin', $this->getLoggedInUsername($client));

        $client->request('GET', '/en/logout');
        $this->assertNull($this->getLoggedInUsername($client));

        $crawler = $client->request('GET', '/en/admin');
        $client->submit($crawler->filter('form.demo-user input[value="editor"]')->closest('form')->form());
        $this->assertSame('editor', $this->getLoggedInUsername($client));

        $client->request('GET', '/en/logout');
        $this->assertNull($this->getLoggedInUsername($client));
    }

    public function testDemoLoginRejectsInvalidUsersAndTokens(): void
    {
        $client = $this->createAnonymousClient();

        $crawler = $client->request('GET', '/en/admin');
        $token = $crawler->filter('form.demo-user input[name="_token"]')->attr('value');

        $client->request('POST', '/en/demo-login', ['username' => 'bonita', '_token' => $token]);
        $this->assertNull($this->getLoggedInUsername($client));

        $client->request('POST', '/en/demo-login', ['username' => 'admin', '_token' => 'invalid']);
        $this->assertNull($this->getLoggedInUsername($client));
    }

    public function testEditorsCanOnlyEditProductsTheyManage(): void
    {
        $client = static::createClient();
        $editor = $this->findDemoUser(DemoUser::Editor);
        $client->loginUser($editor);

        $managedPlant = $this->findPlant(['manager' => $editor]);
        $client->request('GET', sprintf('/en/admin/plant/%d/edit', $managedPlant->getId()));
        $this->assertResponseIsSuccessful();

        $otherPlant = $this->findPlantManagedBySomeoneElse($editor);
        $client->request('GET', sprintf('/en/admin/plant/%d/edit', $otherPlant->getId()));
        $this->assertResponseStatusCodeSame(403);

        $client->request('POST', sprintf('/en/admin/plant/%d/archive', $otherPlant->getId()));
        $this->assertResponseStatusCodeSame(403);
    }

    public function testOnlyAdminsCanManageUsers(): void
    {
        $client = static::createClient();

        $client->loginUser($this->findDemoUser(DemoUser::Editor));
        $client->request('GET', '/en/admin/user');
        $this->assertResponseStatusCodeSame(403);

        $client->loginUser($this->findDemoUser(DemoUser::Admin));
        $client->request('GET', '/en/admin/user');
        $this->assertResponseIsSuccessful();
    }

    public function testReadersCannotRegenerateFixtures(): void
    {
        $client = static::createClient();
        $client->loginUser($this->findDemoUser(DemoUser::Reader));

        $client->request('POST', '/en/admin/regenerate-fixtures');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testRegeneratingFixturesRequiresCsrfToken(): void
    {
        $client = static::createClient();
        $client->loginUser($this->findDemoUser(DemoUser::Admin));

        $client->request('POST', '/en/admin/regenerate-fixtures', ['_token' => 'invalid']);
        $this->assertResponseRedirects('/en/admin/regenerate-fixtures');
    }

    #[DataProvider('batchActionUrls')]
    public function testBatchActionsNeedEditorRole(string $batchActionUrl): void
    {
        $client = $this->createAnonymousClient();
        $client->request('POST', $batchActionUrl);
        $this->assertResponseStatusCodeSame(403);

        $client->loginUser($this->findDemoUser(DemoUser::Reader));
        $client->request('POST', $batchActionUrl);
        $this->assertResponseStatusCodeSame(403);
    }

    public static function batchActionUrls(): iterable
    {
        yield ['/en/admin/plant/batch-publish'];
        yield ['/en/admin/supply/batch-archive'];
        yield ['/en/admin/review/batch-approve'];
        yield ['/en/admin/pending-review/batch-reject'];
        yield ['/en/admin/customer/batch-confirm'];
    }

    public function testBatchActionsRequireCsrfToken(): void
    {
        $client = static::createClient();
        $client->loginUser($this->findDemoUser(DemoUser::Editor));
        $draftId = $this->findPlant(['status' => ProductStatus::Draft])->getId();

        $client->request('POST', '/en/admin/plant/batch-publish', [
            'batchActionName' => 'batchPublish',
            'batchActionEntityIds' => [$draftId],
            'entityFqcn' => Product::class,
            'batchActionCsrfToken' => 'invalid',
        ]);

        $this->assertResponseStatusCodeSame(403);
        $this->assertSame(ProductStatus::Draft, $this->findPlant(['id' => $draftId])->getStatus());
    }

    public function testFieldPermissionsHideCustomerPrivateData(): void
    {
        $client = $this->createAnonymousClient();
        $customerId = $this->getEntityManager()->getConnection()->fetchOne('SELECT id FROM customer WHERE ip_address IS NOT NULL LIMIT 1');

        $crawler = $client->request('GET', sprintf('/en/admin/customer/%d', $customerId));
        $this->assertResponseIsSuccessful();
        $this->assertCount(0, $crawler->filter('.field-text:contains("IP")'));

        $client->loginUser($this->findDemoUser(DemoUser::Admin));
        $crawler = $client->request('GET', sprintf('/en/admin/customer/%d', $customerId));
        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('.field-text:contains("IP")'));
    }

    public function testFeatureTourLinksToExistingPages(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/en/admin/feature-tour');
        $this->assertResponseIsSuccessful();

        $urls = array_unique($crawler->filter('.feature-tour-links a[href^="/"]')->each(
            static fn ($link): string => (string) $link->attr('href'),
        ));
        $this->assertNotEmpty($urls);

        foreach ($urls as $url) {
            $client->request('GET', $url);
            $this->assertLessThan(400, $client->getResponse()->getStatusCode(), sprintf('The URL "%s" is not available.', $url));
        }
    }

    public function testPendingModerationOnlyListsPendingReviews(): void
    {
        $client = static::createClient();
        $pendingIds = $this->getEntityManager()->getConnection()->fetchFirstColumn('SELECT id FROM review WHERE status = ?', [ReviewStatus::Pending->value]);

        $crawler = $client->request('GET', '/en/admin/pending-review');
        $this->assertResponseIsSuccessful();

        $listedIds = $this->getListedEntityIds($crawler);
        $this->assertNotEmpty($listedIds);
        $this->assertSame([], array_values(array_diff($listedIds, $pendingIds)));
    }

    public function testPlantAndSupplyControllersOnlyListTheirProducts(): void
    {
        $client = static::createClient();
        // admins also see the drafts and the archived products, which are the easiest ones to miss
        $client->loginUser($this->findDemoUser(DemoUser::Admin));

        $crawler = $client->request('GET', '/en/admin/plant');
        $this->assertResponseIsSuccessful();
        $listedPlantIds = $this->getListedEntityIds($crawler);
        $this->assertNotEmpty($listedPlantIds);
        $this->assertSame([], array_values(array_diff($listedPlantIds, $this->fetchProductIds(true))));

        $crawler = $client->request('GET', '/en/admin/supply');
        $this->assertResponseIsSuccessful();
        $listedSupplyIds = $this->getListedEntityIds($crawler);
        $this->assertNotEmpty($listedSupplyIds);
        $this->assertSame([], array_values(array_diff($listedSupplyIds, $this->fetchProductIds(false))));
    }

    public function testOrdersCsvExportNeedsEditorRole(): void
    {
        $client = $this->createAnonymousClient();

        $client->request('GET', '/en/admin/order/export');
        $this->assertResponseStatusCodeSame(403);

        $client->loginUser($this->findDemoUser(DemoUser::Reader));
        $client->request('GET', '/en/admin/order/export');
        $this->assertResponseStatusCodeSame(403);

        $client->loginUser($this->findDemoUser(DemoUser::Editor));
        $client->request('GET', '/en/admin/order/export');
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'text/csv; charset=UTF-8');

        // the content of streamed responses is only available in the response of the browser
        $rows = array_map(
            static fn (string $row): array => str_getcsv($row, escape: ''),
            explode("\n", trim($client->getInternalResponse()->getContent())),
        );

        $this->assertSame(['Order number', 'Status'], \array_slice($rows[0], 0, 2));
        $this->assertCount($this->getEntityManager()->getRepository(Order::class)->count([]) + 1, $rows);
    }

    public function testOrderPrintPageRenders(): void
    {
        $client = static::createClient();
        $order = $this->getEntityManager()->getRepository(Order::class)->findOneBy([])
            ?? throw new \RuntimeException('There are no orders. Regenerate the fixtures.');

        $client->request('GET', sprintf('/en/admin/order/%d/print', $order->getId()));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', (string) $order->getNumber());
    }

    public function testDeletingCustomerWithOrdersKeepsIt(): void
    {
        $client = static::createClient();
        $client->loginUser($this->findDemoUser(DemoUser::Admin));
        $customer = $this->findCustomerWithOrders();
        $customerId = $customer->getId();
        $numOfOrders = $customer->getOrders()->count();

        $crawler = $client->request('GET', '/en/admin/customer');
        $token = $crawler->filter('input[name="token"]')->attr('value');
        $client->request('POST', sprintf('/en/admin/customer/%d/delete', $customerId), ['token' => $token]);

        // an invalid token redirects to the dashboard instead, without running the delete logic
        $this->assertResponseRedirects('/en/admin/customer');
        $client->followRedirect();
        $this->assertSelectorExists('.alert-danger');

        $connection = $this->getEntityManager()->getConnection();
        $this->assertSame(1, (int) $connection->fetchOne('SELECT COUNT(*) FROM customer WHERE id = ?', [$customerId]));
        $this->assertSame($numOfOrders, (int) $connection->fetchOne('SELECT COUNT(*) FROM customer_order WHERE customer_id = ?', [$customerId]));
    }

    public function testConfirmingCustomerNeedsEditorRole(): void
    {
        $client = static::createClient();
        $customerId = $this->getEntityManager()->getRepository(Customer::class)->findOneBy(['isConfirmed' => false])?->getId()
            ?? throw new \RuntimeException('All the customers are confirmed. Regenerate the fixtures.');
        $confirmUrl = sprintf('/en/admin/customer/%d/confirm', $customerId);

        $client->request('POST', $confirmUrl);
        $this->assertResponseStatusCodeSame(403);
        $this->assertFalse($this->isCustomerConfirmed($customerId));

        $client->loginUser($this->findDemoUser(DemoUser::Reader));
        $client->request('POST', $confirmUrl);
        $this->assertResponseStatusCodeSame(403);
        $this->assertFalse($this->isCustomerConfirmed($customerId));

        // the test and the request must share the database connection, so the
        // change can be rolled back: tests run against the same database as the demo
        $client->disableReboot();
        $client->loginUser($this->findDemoUser(DemoUser::Editor));
        $connection = $this->getEntityManager()->getConnection();
        $connection->beginTransaction();

        try {
            $client->request('POST', $confirmUrl);
            $this->assertResponseRedirects('/en/admin/customer');
            $this->assertTrue($this->isCustomerConfirmed($customerId));
        } finally {
            $connection->rollBack();
        }

        $this->assertFalse($this->isCustomerConfirmed($customerId));
    }

    private function getLoggedInUsername(KernelBrowser $client): ?string
    {
        $crawler = $client->request('GET', '/en/admin');

        $username = null;
        foreach (DemoUser::cases() as $demoUser) {
            $isCurrent = 1 === $crawler->filter(sprintf('form.demo-user.is-current:has(input[value="%s"])', $demoUser->value))->count();
            if ($isCurrent) {
                $username = $demoUser->value;
            }
        }

        return $username;
    }

    public function testDashboardShowsDemoUsersAndLogsOutTheCurrentOne(): void
    {
        $client = static::createClient();
        $client->loginUser($this->findDemoUser(DemoUser::Editor));

        $crawler = $client->request('GET', '/en/admin');
        $this->assertCount(3, $crawler->filter('.demo-user img[src*="uploads/avatars/"]'));
        $this->assertSelectorTextContains('.demo-user.is-current', DemoUser::Editor->fullName());
        $this->assertGreaterThan(0, $crawler->filter('.dashboard-list-author img')->count());
        $this->assertGreaterThan(0, $crawler->filter('.dashboard-list-product img[src*="uploads/products/"]')->count());

        $client->click($crawler->filter('.demo-user.is-current a')->link());
        $this->assertResponseRedirects();
        $crawler = $client->followRedirect();
        $this->assertCount(0, $crawler->filter('.demo-user.is-current'));
    }

    public function testOrderListingShowsProgressAndProductThumbnails(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/en/admin/order');

        $this->assertResponseIsSuccessful();
        $this->assertCount(0, $crawler->filter('th[data-column="lines"]'));

        $rows = $crawler->filter('table.datagrid tbody tr[data-id]');
        $this->assertGreaterThan(0, $rows->count());
        $rows->each(function (Crawler $row): void {
            $this->assertCount(4, $row->filter('.order-progress-step'));
            $numOfThumbnails = $row->filter('.order-products img')->count();
            $this->assertGreaterThan(0, $numOfThumbnails);
            $this->assertLessThanOrEqual(5, $numOfThumbnails);
        });
    }

    public function testDetailPagesUseDescriptiveAndEscapedTitles(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $order = $entityManager->getRepository(Order::class)->findOneBy([]);
        $crawler = $client->request('GET', sprintf('/en/admin/order/%d', $order->getId()));
        $this->assertStringStartsWith(sprintf('Order %s (€', $order->getNumber()), $crawler->filter('title')->text());

        $client->loginUser($this->findDemoUser(DemoUser::Admin));
        $crawler = $client->request('GET', sprintf('/en/admin/order/%d/edit', $order->getId()));
        $this->assertSame(sprintf('Edit order %s', $order->getNumber()), $crawler->filter('title')->text());

        $family = $entityManager->getRepository(Family::class)->findOneBy([]);
        $originalName = $family->getName();
        $family->setName('<b>Family 1</b>');
        $entityManager->flush();

        try {
            $client->request('GET', sprintf('/en/admin/family/%d', $family->getId()));
            $this->assertStringContainsString('Family &lt;b&gt;Family 1&lt;/b&gt;', (string) $client->getResponse()->getContent());
            $this->assertStringNotContainsString('Family <b>Family 1</b>', (string) $client->getResponse()->getContent());
        } finally {
            $family = $entityManager->getRepository(Family::class)->find($family->getId());
            $family->setName($originalName);
            $entityManager->flush();
        }
    }

    private function findDemoUser(DemoUser $demoUser): User
    {
        return $this->getEntityManager()->getRepository(User::class)->findOneBy(['email' => $demoUser->email()])
            ?? throw new \RuntimeException(sprintf('The demo user "%s" does not exist. Regenerate the fixtures.', $demoUser->value));
    }

    /**
     * @param array<string, mixed> $criteria
     */
    private function findPlant(array $criteria): Product
    {
        $queryBuilder = $this->createPlantQueryBuilder();

        foreach ($criteria as $property => $value) {
            $queryBuilder
                ->andWhere(sprintf('product.%s = :%s', $property, $property))
                ->setParameter($property, $value);
        }

        return $queryBuilder->setMaxResults(1)->getQuery()->getOneOrNullResult()
            ?? throw new \RuntimeException('No plant matches the given criteria. Regenerate the fixtures.');
    }

    private function findPlantManagedBySomeoneElse(User $user): Product
    {
        return $this->createPlantQueryBuilder()
            ->andWhere('product.manager != :user')
            ->setParameter('user', $user)
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleResult();
    }

    private function createPlantQueryBuilder(): QueryBuilder
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('product')
            ->from(Product::class, 'product')
            ->innerJoin('product.family', 'family')
            ->andWhere('family.isPlantFamily = true');
    }

    private function findCustomerWithOrders(): Customer
    {
        return $this->getEntityManager()->createQuery('SELECT customer FROM App\Entity\Customer customer JOIN customer.orders customerOrder')
            ->setMaxResults(1)
            ->getSingleResult();
    }

    private function isCustomerConfirmed(int $customerId): bool
    {
        return (bool) $this->getEntityManager()->getConnection()->fetchOne('SELECT is_confirmed FROM customer WHERE id = ?', [$customerId]);
    }

    /**
     * @return list<mixed>
     */
    private function fetchProductIds(bool $inPlantFamily): array
    {
        return $this->getEntityManager()->getConnection()->fetchFirstColumn(
            'SELECT product.id FROM product INNER JOIN family ON family.id = product.family_id WHERE family.is_plant_family = ?',
            [(int) $inPlantFamily],
        );
    }

    /**
     * @return list<string>
     */
    private function getListedEntityIds(Crawler $crawler): array
    {
        return $crawler->filter('tr[data-id]')->each(
            static fn (Crawler $row): string => (string) $row->attr('data-id'),
        );
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }
}
