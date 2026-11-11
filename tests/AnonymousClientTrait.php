<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;

trait AnonymousClientTrait
{
    /**
     * The demo logs visitors in as admins automatically until they log out.
     */
    private function createAnonymousClient(): KernelBrowser
    {
        $client = static::createClient();
        $client->request('GET', '/en/logout');

        return $client;
    }
}
