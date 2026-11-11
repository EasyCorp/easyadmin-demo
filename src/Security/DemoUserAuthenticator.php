<?php

namespace App\Security;

use App\Enum\DemoUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * Logs visitors in with one click, so they can compare what each role can do.
 *
 * This is only acceptable because this demo runs on your own computer and uses fake
 * data: real applications must verify the credentials of their users.
 */
final class DemoUserAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    public const LOGIN_ROUTE = 'demo_login';
    public const CSRF_TOKEN_ID = 'demo_login';

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return self::LOGIN_ROUTE === $request->attributes->get('_route') && $request->isMethod('POST');
    }

    public function authenticate(Request $request): Passport
    {
        $demoUser = DemoUser::tryFrom($request->request->getString('username'));
        if (null === $demoUser) {
            throw new CustomUserMessageAuthenticationException('demo.login_failed');
        }

        return new SelfValidatingPassport(
            new UserBadge($demoUser->email()),
            [new CsrfTokenBadge(self::CSRF_TOKEN_ID, $request->request->getString('_token'))],
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return $this->redirectToDashboard($request);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $this->addFlash($request, 'danger', 'demo.login_failed');

        return $this->redirectToDashboard($request);
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        $this->addFlash($request, 'warning', 'demo.login_required');

        return $this->redirectToDashboard($request);
    }

    private function redirectToDashboard(Request $request): RedirectResponse
    {
        return new RedirectResponse($this->urlGenerator->generate('admin', ['_locale' => $request->getLocale()]));
    }

    private function addFlash(Request $request, string $type, string $message): void
    {
        $session = $request->getSession();
        if ($session instanceof FlashBagAwareSessionInterface) {
            $session->getFlashBag()->add($type, $message);
        }
    }
}
