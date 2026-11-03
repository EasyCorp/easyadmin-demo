<?php

namespace App\Controller\Admin;

use App\Story\InitialStateStory;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class FixturesController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[AdminRoute('/regenerate-fixtures', name: 'regenerate_fixtures', options: ['methods' => ['GET', 'POST']])]
    #[IsGranted('ROLE_ADMIN')]
    public function regenerateFixtures(Request $request, KernelInterface $kernel): Response
    {
        if ('POST' === $request->getMethod()) {
            if (!$this->isCsrfTokenValid('regenerate_fixtures', $request->request->getString('_token'))) {
                $this->addFlash('danger', 'demo.invalid_csrf_token');

                return $this->redirectToRoute('admin_regenerate_fixtures');
            }

            $this->resetDatabase($kernel);
            InitialStateStory::load();

            $this->addFlash('success', 'fixtures.regenerated');

            return $this->redirectToRoute('admin');
        }

        return $this->render('admin/regenerate_fixtures.html.twig');
    }

    // this is done only for demo purposes: in a real application, fixtures are created
    // when running tests or using the command line
    // (see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html)
    private function resetDatabase(KernelInterface $kernel): void
    {
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $output = new BufferedOutput();

        // the security system already queried the logged-in user, so the connection must be
        // closed; otherwise, it keeps pointing to the deleted file and can't write to the new one
        $this->entityManager->clear();
        $this->entityManager->getConnection()->close();

        // invoking the 'doctrine:database:drop' command doesn't work
        (new Filesystem())->remove($kernel->getProjectDir().'/var/data.db');

        // see https://symfony.com/doc/current/console/command_in_controller.html
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
