<?php

namespace App\Controller;

use Symfony\Component\Routing\Attribute\Route;

class DemoSecurityController
{
    #[Route('/{_locale}/demo-login', name: 'demo_login', methods: ['POST'])]
    public function login(): never
    {
        throw new \LogicException('This code is never executed because the DemoUserAuthenticator handles this route.');
    }

    #[Route('/{_locale}/logout', name: 'demo_logout')]
    public function logout(): never
    {
        throw new \LogicException('This code is never executed because the firewall handles this route.');
    }
}
