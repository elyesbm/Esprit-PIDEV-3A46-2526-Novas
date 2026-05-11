<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * GoogleAuthenticator
 *
 * Gere la connexion / creation de compte via Google OAuth 2.0.
 */
class GoogleAuthenticator extends OAuth2Authenticator implements AuthenticationEntryPointInterface
{
    public function __construct(
        private ClientRegistry $clientRegistry,
        private EntityManagerInterface $em,
        private RouterInterface $router,
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client) {
                /** @var GoogleUser $googleUser */
                $googleUser = $client->fetchUserFromToken($accessToken);

                $email = trim((string) $googleUser->getEmail());
                if ($email === '') {
                    throw new AuthenticationException('Email Google introuvable.');
                }

                $existingUser = $this->em->getRepository(User::class)
                    ->findOneBy(['EMAIL' => $email]);

                if ($existingUser instanceof User) {
                    return $existingUser;
                }

                $user = new User();
                $user->setEMAIL($email);
                $user->setNOM(trim((string) ($googleUser->getLastName() ?: $googleUser->getName() ?: 'Utilisateur')));
                $user->setPRENOM(trim((string) ($googleUser->getFirstName() ?: '')));
                $user->setROLE('ROLE_USER');
                $user->setPassword('');

                $avatar = $googleUser->getAvatar();
                if (is_string($avatar) && $avatar !== '') {
                    $user->setIMAGE($avatar);
                }
                $user->setACTIF(true);

                $this->em->persist($user);
                $this->em->flush();

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();
        if (! $user instanceof User) {
            return new RedirectResponse($this->router->generate('app_login'));
        }

        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return new RedirectResponse($this->router->generate('app_admin_dashboard'));
        }

        return new RedirectResponse($this->router->generate('app_user_profile'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $session = $request->hasSession() ? $request->getSession() : null;
        if ($session !== null && method_exists($session, 'getFlashBag')) {
            $session->getFlashBag()->add(
                'error',
                'Connexion Google echouee : ' . strtr($exception->getMessageKey(), $exception->getMessageData())
            );
        }

        return new RedirectResponse($this->router->generate('app_login'));
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new RedirectResponse($this->router->generate('app_login'));
    }
}
