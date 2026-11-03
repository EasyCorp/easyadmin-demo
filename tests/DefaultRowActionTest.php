<?php

namespace App\Tests;

use App\Entity\User;
use App\Enum\DemoUser;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultRowActionTest extends WebTestCase
{
    use AnonymousClientTrait;

    private const ALL_ROLES_LISTINGS = ['plant', 'supply', 'family', 'tag', 'order', 'customer', 'review', 'pending-review', 'supplier'];

    #[DataProvider('listings')]
    public function testAllRowsOfListingsAreClickable(?DemoUser $demoUser, string $listing): void
    {
        $client = null === $demoUser ? $this->createAnonymousClient() : static::createClient();
        if (null !== $demoUser) {
            $user = static::getContainer()->get(EntityManagerInterface::class)->getRepository(User::class)->findOneBy(['email' => $demoUser->email()]);
            $client->loginUser($user);
        }

        $crawler = $client->request('GET', '/en/admin/'.$listing);
        $this->assertResponseIsSuccessful();

        $rows = $crawler->filter('table.datagrid tbody tr[data-id]');
        $this->assertGreaterThan(0, $rows->count());
        $this->assertSame($rows->count(), $rows->filter('[data-default-action-url]')->count());
    }

    public static function listings(): iterable
    {
        foreach (self::ALL_ROLES_LISTINGS as $listing) {
            yield 'anonymous, '.$listing => [null, $listing];
            foreach (DemoUser::cases() as $demoUser) {
                yield $demoUser->value.', '.$listing => [$demoUser, $listing];
            }
        }

        yield 'admin, user' => [DemoUser::Admin, 'user'];
    }
}
