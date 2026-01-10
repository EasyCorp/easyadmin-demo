<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DefaultController extends AbstractController
{
    #[Route('/{_locale}/', name: 'homepage')]
    public function index(): Response
    {
        return $this->redirectToRoute('admin');
    }

    #[Route('/')]
    public function indexNoLocale(): Response
    {
        return $this->redirectToRoute('homepage', ['_locale' => 'en']);
    }
}
