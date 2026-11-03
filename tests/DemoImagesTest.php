<?php

namespace App\Tests;

use App\Entity\Customer;
use App\Entity\Family;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DemoImagesTest extends WebTestCase
{
    public function testAllFixtureImagesExist(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $publicDir = $client->getKernel()->getProjectDir().'/public/uploads/';

        $imagePaths = [
            ...array_map(static fn (Product $product): string => 'products/'.$product->getImage(), $entityManager->getRepository(Product::class)->findAll()),
            ...array_map(static fn (Family $family): string => 'families/'.$family->getCover(), $entityManager->getRepository(Family::class)->findAll()),
            ...array_map(static fn (User $user): string => 'avatars/'.$user->getAvatar(), $entityManager->getRepository(User::class)->findAll()),
            ...array_map(static fn (Customer $customer): string => 'avatars/'.$customer->getAvatar(), array_filter($entityManager->getRepository(Customer::class)->findAll(), static fn (Customer $customer): bool => null !== $customer->getAvatar())),
        ];

        $this->assertCount(25 + 3 + 8 + 18, $imagePaths);
        foreach ($imagePaths as $imagePath) {
            $this->assertFileExists($publicDir.$imagePath);
        }
    }
}
