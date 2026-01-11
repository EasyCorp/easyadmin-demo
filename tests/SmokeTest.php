<?php

namespace App\Tests;

use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\Series;
use App\Entity\Subscriber;
use App\Entity\Tag;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Smoke test to ensure all pages load without 500 errors.
 */
class SmokeTest extends WebTestCase
{
    /**
     * Test public pages without authentication.
     *
     * @dataProvider publicUrlProvider
     */
    public function testPublicPages(string $url, array $expectedCodes): void
    {
        $client = static::createClient();
        $client->request('GET', $url);

        $this->assertNoServerError($client, $url, $expectedCodes);
    }

    public static function publicUrlProvider(): iterable
    {
        // Homepage
        yield 'homepage' => ['/', [302]];
        yield 'homepage_en' => ['/en/', [200, 302]];
        yield 'homepage_es' => ['/es/', [200, 302]];

        // Admin dashboard
        yield 'admin_dashboard_en' => ['/en/admin', [200]];
        yield 'admin_dashboard_es' => ['/es/admin', [200]];

        // Admin index pages (publicly accessible in this demo)
        yield 'admin_category_index' => ['/en/admin/category', [200]];
        yield 'admin_comment_index' => ['/en/admin/comment', [200]];
        yield 'admin_post_index' => ['/en/admin/post', [200]];
        yield 'admin_series_index' => ['/en/admin/series', [200]];
        yield 'admin_subscriber_index' => ['/en/admin/subscriber', [200]];
        yield 'admin_tag_index' => ['/en/admin/tag', [200]];
        yield 'admin_user_index' => ['/en/admin/user', [200]];

        // New/create pages (403 expected - require write permission)
        yield 'admin_category_new' => ['/en/admin/category/new', [200, 403]];
        yield 'admin_comment_new' => ['/en/admin/comment/new', [200, 403]];
        yield 'admin_post_new' => ['/en/admin/post/new', [200, 403]];
        yield 'admin_series_new' => ['/en/admin/series/new', [200, 403]];
        yield 'admin_subscriber_new' => ['/en/admin/subscriber/new', [200, 403]];
        yield 'admin_tag_new' => ['/en/admin/tag/new', [200, 403]];
        yield 'admin_user_new' => ['/en/admin/user/new', [200, 403]];
    }

    /**
     * Test entity detail pages.
     *
     * @dataProvider entityDetailUrlProvider
     */
    public function testEntityDetailPages(string $entityClass, string $urlPattern): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $entity = $em->getRepository($entityClass)->findOneBy([]);

        if (null === $entity) {
            $this->markTestSkipped(sprintf('No %s entity found in database', $entityClass));
        }

        $url = sprintf($urlPattern, $entity->getId());
        $client->request('GET', $url);

        // Detail pages should return 200 or 403 (never 500)
        $this->assertNoServerError($client, $url, [200, 403]);
    }

    public static function entityDetailUrlProvider(): iterable
    {
        yield 'category_detail' => [Category::class, '/en/admin/category/%d'];
        yield 'comment_detail' => [Comment::class, '/en/admin/comment/%d'];
        yield 'post_detail' => [Post::class, '/en/admin/post/%d'];
        yield 'series_detail' => [Series::class, '/en/admin/series/%d'];
        yield 'subscriber_detail' => [Subscriber::class, '/en/admin/subscriber/%d'];
        yield 'tag_detail' => [Tag::class, '/en/admin/tag/%d'];
        yield 'user_detail' => [User::class, '/en/admin/user/%d'];
    }

    /**
     * Test entity edit pages.
     *
     * @dataProvider entityEditUrlProvider
     */
    public function testEntityEditPages(string $entityClass, string $urlPattern): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $entity = $em->getRepository($entityClass)->findOneBy([]);

        if (null === $entity) {
            $this->markTestSkipped(sprintf('No %s entity found in database', $entityClass));
        }

        $url = sprintf($urlPattern, $entity->getId());
        $client->request('GET', $url);

        // Edit pages should return 200 or 403 (never 500)
        $this->assertNoServerError($client, $url, [200, 403]);
    }

    public static function entityEditUrlProvider(): iterable
    {
        yield 'category_edit' => [Category::class, '/en/admin/category/%d/edit'];
        yield 'comment_edit' => [Comment::class, '/en/admin/comment/%d/edit'];
        yield 'post_edit' => [Post::class, '/en/admin/post/%d/edit'];
        yield 'series_edit' => [Series::class, '/en/admin/series/%d/edit'];
        yield 'subscriber_edit' => [Subscriber::class, '/en/admin/subscriber/%d/edit'];
        yield 'tag_edit' => [Tag::class, '/en/admin/tag/%d/edit'];
        yield 'user_edit' => [User::class, '/en/admin/user/%d/edit'];
    }

    private function assertNoServerError(KernelBrowser $client, string $url, array $acceptedCodes): void
    {
        $statusCode = $client->getResponse()->getStatusCode();

        // Ensure no 5xx server error
        $this->assertLessThan(
            500,
            $statusCode,
            sprintf('URL "%s" returned server error %d', $url, $statusCode)
        );

        // Verify it's one of the expected codes
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
