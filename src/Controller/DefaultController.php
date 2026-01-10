<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DefaultController extends AbstractController
{
    public function __construct(
        #[Autowire('%kernel.enabled_locales%')] private array $enabledLocales,
    ) {
    }

    #[Route('/{_locale}/', name: 'homepage')]
    public function index(): Response
    {
        return $this->redirectToRoute('admin');
    }

    #[Route('/')]
    public function indexNoLocale(Request $request): Response
    {
        $preferredLocale = $request->getPreferredLanguage($this->enabledLocales) ?? 'en';

        return $this->redirectToRoute('homepage', ['_locale' => $preferredLocale]);
    }
}
