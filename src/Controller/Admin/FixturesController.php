<?php

namespace App\Controller\Admin;

use App\Story\InitialStateStory;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;

class FixturesController extends AbstractController
{
    #[Route('/{_locale<%app.supported_locales%>}/admin/regenerate-fixtures', name: 'admin_regenerate_fixtures', methods: ['GET', 'POST'])]
    public function regenerateFixtures(Request $request, KernelInterface $kernel): Response
    {
        if ('POST' === $request->getMethod()) {
            $this->resetDatabase($kernel);
            InitialStateStory::load();

            $this->addFlash('success', 'All the application fixtures have been successfully regenerated.');

            return $this->redirectToRoute('admin');
        }

        return $this->render('admin/regenerate_fixtures.html.twig');
    }

    // This is done only for demo purposes. In a real application, fixtures are
    // created when running tests or using the command line.
    // See https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html
    private function resetDatabase(KernelInterface $kernel): void
    {
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $output = new BufferedOutput();

        // invoking the 'doctrine:database:drop' command doesn't work
        (new Filesystem())->remove($kernel->getProjectDir().'/var/data.db');

        // See https://symfony.com/doc/current/console/command_in_controller.html
        $createCommand = new ArrayInput([
            'command' => 'doctrine:database:create',
            '--no-interaction' => true,
        ]);
        $application->run($createCommand, $output);

        $schemaCommand = new ArrayInput([
            'command' => 'doctrine:schema:create',
            '--no-interaction' => true,
        ]);
        $application->run($schemaCommand, $output);
    }
}
