<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Enum\DemoUser;
use App\Security\DemoUserAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

/**
 * Logs visitors in as the admin demo user, so the first page they see shows all the
 * features of the demo. After logging out, they browse the demo as anonymous visitors.
 *
 * This is only acceptable because this demo runs on your own computer and uses fake
 * data: real applications must never log users in without verifying their credentials.
 */
final class DemoAutoLoginSubscriber implements EventSubscriberInterface
{
    private const FIREWALL_NAME = 'main';
    private const LOGGED_OUT_SESSION_KEY = 'demo_logged_out';

    public function __construct(
        private readonly Security $security,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // the firewall (priority 8) must load the user from the session before this runs,
            // and EasyAdmin (priority 1) must create its context after the user is logged in
            KernelEvents::REQUEST => ['logInAnonymousVisitors', 7],
            // the session must be invalidated (priority 0) before storing the flag in it
            LogoutEvent::class => ['rememberLogout', -10],
            LoginSuccessEvent::class => 'forgetLogout',
        ];
    }

    public function logInAnonymousVisitors(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$event->isMainRequest() || !$request->isMethodSafe() || !$request->hasSession()) {
            return;
        }

        if (self::FIREWALL_NAME !== $this->security->getFirewallConfig($request)?->getName()) {
            return;
        }

        if (null !== $this->security->getUser() || $request->getSession()->get(self::LOGGED_OUT_SESSION_KEY)) {
            return;
        }

        $admin = $this->entityManager->getRepository(User::class)->findOneBy(['email' => DemoUser::Admin->email()]);
        if (null === $admin) {
            return;
        }

        // the response is ignored to render the requested page instead of redirecting to the dashboard
        $this->security->login($admin, DemoUserAuthenticator::class, self::FIREWALL_NAME);
    }

    public function rememberLogout(LogoutEvent $event): void
    {
        $request = $event->getRequest();
        if ($request->hasSession()) {
            $request->getSession()->set(self::LOGGED_OUT_SESSION_KEY, true);
        }
    }

    public function forgetLogout(LoginSuccessEvent $event): void
    {
        $request = $event->getRequest();
        if ($request->hasSession()) {
            $request->getSession()->remove(self::LOGGED_OUT_SESSION_KEY);
        }
    }
}
