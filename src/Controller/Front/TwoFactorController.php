<?php

namespace App\Controller\Front;

use App\Entity\User;
use App\Service\TwoFactorAuthService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

#[Route('/2fa')]
class TwoFactorController extends AbstractController
{
    public function __construct(
        private TwoFactorAuthService $twoFactorService,
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * Afficher et configurer la 2FA
     */
    #[Route('/setup', name: 'app_2fa_setup')]
    public function setup(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $secret = null;
        $qrCode = null;
        $backupCodes = [];

        if ($request->isMethod('POST')) {
            // Générer une nouvelle clé secrète
            $secret = $this->twoFactorService->generateSecret();
            $qrCode = $this->twoFactorService->generateQrCode($user->getEMAIL(), $secret);
            $backupCodes = $this->twoFactorService->generateBackupCodes(10);

            // Stocker dans la session pour confirmation
            $request->getSession()->set('2fa_secret', $secret);
            $request->getSession()->set('2fa_backup_codes', $backupCodes);

            return $this->render('front/2fa/setup_confirm.html.twig', [
                'secret' => $secret,
                'qrCode' => $qrCode,
                'backupCodes' => $backupCodes,
            ]);
        }

        return $this->render('front/2fa/setup.html.twig');
    }

    /**
     * Confirmer la 2FA
     */
    #[Route('/setup-confirm', name: 'app_2fa_setup_confirm', methods: ['POST'])]
    public function setupConfirm(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $session = $request->getSession();
        $secret = $session->get('2fa_secret');
        $backupCodes = $session->get('2fa_backup_codes');

        if (!$secret) {
            $this->addFlash('error', 'Veuillez d\'abord générer une clé secrète.');
            return $this->redirectToRoute('app_2fa_setup');
        }

        $code = $request->request->get('code');
        if (!$code || !$this->twoFactorService->validateCode($secret, $code)) {
            $this->addFlash('error', 'Code invalide. Veuillez réessayer.');
            return $this->redirectToRoute('app_2fa_setup');
        }

        // Activer 2FA pour l'utilisateur
        /** @var User $user */
        $user->setTwoFactorSecret($secret);
        $user->enableTwoFactor();
        $user->setBackupCodes($backupCodes);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Nettoyer la session
        $session->remove('2fa_secret');
        $session->remove('2fa_backup_codes');

        $this->addFlash('success', 'Authentification à deux facteurs activée avec succès!');
        return $this->redirectToRoute('app_user_profile');
    }

    /**
     * Désactiver la 2FA
     */
    #[Route('/disable', name: 'app_2fa_disable', methods: ['POST'])]
    public function disable(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Vérifier le mot de passe avant de désactiver
        if ($request->request->get('password')) {
            $passwordHasher = $this->container->get('security.password_hasher');
            if (!$passwordHasher->isPasswordValid($user, $request->request->get('password'))) {
                $this->addFlash('error', 'Mot de passe incorrect.');
                return $this->redirectToRoute('app_user_profile');
            }
        }

        /** @var User $user */
        $user->disableTwoFactor();
        $user->setTwoFactorSecret(null);
        $user->setBackupCodes(null);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->addFlash('success', 'Authentification à deux facteurs désactivée.');
        return $this->redirectToRoute('app_user_profile');
    }

    /**
     * Vérifier le code 2FA après la connexion
     */
    #[Route('/verify', name: 'app_2fa_verify')]
    public function verify(Request $request, SessionInterface $session, \Symfony\Bundle\SecurityBundle\Security $security): Response
    {
        $userPending = $session->get('2fa_user_pending');
        if (!$userPending) {
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            // Récupérer le code (TOTP ou backup)
            $codeInput = $request->request->get('code') ?? $request->request->get('backup_code');
            
            if (!$codeInput) {
                $this->addFlash('error', 'Code requis.');
                return $this->redirectToRoute('app_2fa_verify');
            }

            // Charger l'utilisateur depuis la base de données
            $userRepo = $this->entityManager->getRepository(User::class);
            $authenticatingUser = $userRepo->find($userPending['id']);

            if (!$authenticatingUser) {
                return $this->redirectToRoute('app_login');
            }

            $isValid = false;

            // Valider le code TOTP
            if (strlen($codeInput) === 6 && ctype_digit($codeInput)) {
                if ($this->twoFactorService->validateCode($authenticatingUser->getTwoFactorSecret(), $codeInput)) {
                    $isValid = true;
                }
            }

            // Vérifier les codes de secours
            if (!$isValid) {
                $backupCodes = $authenticatingUser->getBackupCodes() ?? [];
                if ($this->twoFactorService->validateBackupCode($backupCodes, $codeInput)) {
                    $isValid = true;
                    $authenticatingUser->setBackupCodes($backupCodes);
                    $this->entityManager->persist($authenticatingUser);
                    $this->entityManager->flush();
                    $this->addFlash('warning', 'Code de secours utilisé. Générez une nouvelle liste si possible.');
                }
            }

            if ($isValid) {
                // Authentifier l'utilisateur via le composant Security de Symfony
                return $this->handleSuccessfulAuthentication($authenticatingUser, $request, $security);
            }

            $this->addFlash('error', 'Code invalide.');
        }

        return $this->render('front/2fa/verify.html.twig');
    }

    private function handleSuccessfulAuthentication(User $user, Request $request, \Symfony\Bundle\SecurityBundle\Security $security): Response
    {
        // ✅ Poser un flag AVANT login() pour que TwoFactorAuthenticationListener
        // sache que cet appel vient de la vérification 2FA et ne doit PAS
        // re-intercepter l'événement INTERACTIVE_LOGIN.
        $request->getSession()->set('2fa_just_verified', true);

        // Connecter l'utilisateur officiellement.
        $security->login($user, 'security.authenticator.form_login.main');

        // Nettoyer la session
        $request->getSession()->remove('2fa_user_pending');

        // Rediriger selon les rôles
        $roles = $user->getRoles();
        if (in_array('ROLE_ADMIN', $roles, true)) {
            return $this->redirectToRoute('app_admin_dashboard');
        }
        if (in_array('ROLE_PSY', $roles, true)) {
            return $this->redirectToRoute('app_publications_index');
        }

        return $this->redirectToRoute('app_user_profile');
    }
}
