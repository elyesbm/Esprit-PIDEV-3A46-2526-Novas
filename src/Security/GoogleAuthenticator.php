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
 * Gère la connexion / création de compte via Google OAuth 2.0.
 * Si l'utilisateur n'existe pas encore en BD, un compte est créé
 * automatiquement avec le rôle ROLE_USER.
 */
class GoogleAuthenticator extends OAuth2Authenticator implements AuthenticationEntrypointInterface
{
    public function __construct(
        private ClientRegistry         $clientRegistry,
        private EntityManagerInterface $em,
        private RouterInterface        $router,
    ) {}

    public function supports(Request $request): ?bool
    {
        // Déclenché uniquement sur la route de callback Google
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client      = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client) {
                /** @var GoogleUser $googleUser */
                $googleUser = $client->fetchUserFromToken($accessToken);

                $email = $googleUser->getEmail();

                // Chercher l'utilisateur existant par e-mail Google
                $existingUser = $this->em->getRepository(User::class)
                    ->findOneBy(['EMAIL' => $email]);

                if ($existingUser) {
                    return $existingUser;
                }

                // ── Créer un nouveau compte ───────────────────────────────
                $user = new User();
                $user->setEMAIL($email);
                $user->setNOM($googleUser->getLastName()   ?? $googleUser->getName() ?? 'Utilisateur');
                $user->setPRENOM($googleUser->getFirstName() ?? '');
                $user->setROLE('ROLE_USER');
                // Mot de passe vide car connexion OAuth (pas de mot de passe local)
                $user->setPassword('');
                // Photo de profil Google
                if ($googleUser->getAvatar()) {
                    $user->setIMAGE($googleUser->getAvatar());
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
        $user  = $token->getUser();
        $roles = $user->getRoles();

        if (in_array('ROLE_ADMIN', $roles, true)) {
            return new RedirectResponse($this->router->generate('app_admin_dashboard'));
        }

        return new RedirectResponse($this->router->generate('app_user_profile'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $request->getSession()->getFlashBag()->add(
            'error',
            'Connexion Google échouée : ' . strtr($exception->getMessageKey(), $exception->getMessageData())
        );

        return new RedirectResponse($this->router->generate('app_login'));
    }

    /**
     * Redirige vers la page de login si l'utilisateur n'est pas authentifié.
     */
    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new RedirectResponse($this->router->generate('app_login'));
    }
}
