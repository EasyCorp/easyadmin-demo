<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FamilyCardsTest extends WebTestCase
{
    public function testFamilyListingKeepsTheSortableHeaderAndClickableRows(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/en/admin/family');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('body.family-cards');
        $this->assertSelectorExists('table.datagrid thead th[data-column="name"] a');
        $this->assertSame(3, $crawler->filter('table.datagrid tbody tr[data-default-action-url]')->count());
    }
}
