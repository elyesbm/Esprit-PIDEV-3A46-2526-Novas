<?php

namespace App\EventListener;

use App\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

/**
 * TwoFactorAuthenticationListener
 *
 * Intercepte la connexion réussie (form login) et, si l'utilisateur
 * a activé la 2FA, suspend la session d'authentification Symfony
 * jusqu'à la validation du code TOTP.
 *
 * Logique anti-bypass :
 *   - Si le flag '2fa_just_verified' est présent en session, la connexion
 *     vient du TwoFactorController (code validé) → on laisse passer.
 *   - Sinon → on suspend l'authentification et on demande le code 2FA.
 */
class TwoFactorAuthenticationListener implements EventSubscriberInterface
{
    public function __construct(
        private RouterInterface       $router,
        private RequestStack          $requestStack,
        private TokenStorageInterface $tokenStorage,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            SecurityEvents::INTERACTIVE_LOGIN => 'onInteractiveLogin',
        ];
    }

    public function onInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();

        // Uniquement pour les utilisateurs avec 2FA activée
        if (!($user instanceof User) || !$user->isTwoFactorEnabled()) {
            return;
        }

        $request = $event->getRequest();
        $session = $request->getSession();

        // ✅ Si le flag est présent, la connexion vient de TwoFactorController
        // après validation réussie du code → on ne doit PAS suspendre.
        if ($session->get('2fa_just_verified')) {
            $session->remove('2fa_just_verified'); // Consommer le flag (usage unique)
            return;
        }

        // --- Connexion initiale sans code 2FA → on suspend ---

        // 1. Mémoriser l'utilisateur pour la page de vérification
        $pending = [
            'id'    => $user->getId(),
            'email' => $user->getEMAIL(),
        ];

        // 2. Invalider la session pour effacer le token Symfony persisté en cookie
        $session->invalidate();

        // 3. Réécrire les données 2FA dans la nouvelle session propre
        $request->getSession()->set('2fa_user_pending', $pending);

        // 4. Vider le token en mémoire pour que getUser() retourne null
        //    si l'utilisateur revient sur /login sans saisir le code.
        $this->tokenStorage->setToken(null);
    }
}
