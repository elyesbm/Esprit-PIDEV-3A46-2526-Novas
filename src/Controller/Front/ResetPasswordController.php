<?php

namespace App\Controller\Front;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ResetPasswordController extends AbstractController
{
    /**
     * Étape 1 : formulaire pour saisir son email
     */
    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function forgotPassword(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): Response {
        if ($request->isMethod('POST')) {
            $email = trim((string) $request->request->get('email', ''));
            $user  = $userRepository->findOneBy(['EMAIL' => $email]);

            // Toujours afficher le même message pour ne pas révéler si l'email existe
            if ($user) {
                // Générer un token aléatoire sécurisé
                $token = bin2hex(random_bytes(32)); // 64 chars hex
                $user->setResetToken($token);
                $user->setResetTokenExpiresAt(new \DateTime('+1 hour'));
                $em->flush();

                // Lien de réinitialisation
                $resetUrl = $this->generateUrl('app_reset_password', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);

                // Envoi du mail
                $emailMessage = (new Email())
                    ->from(new \Symfony\Component\Mime\Address('maryemf239@gmail.com', 'NOVAS'))
                    ->to((string) $user->getEMAIL())
                    ->subject('NoVas — Réinitialisation de votre mot de passe')
                    ->html($this->renderView('front/auth/reset_password_email.html.twig', [
                        'user'     => $user,
                        'resetUrl' => $resetUrl,
                        'expiry'   => '1 heure',
                    ]));

                $mailer->send($emailMessage);
            }

            $this->addFlash('success', 'Si cet email est associé à un compte, un lien de réinitialisation vous a été envoyé. Vérifiez votre boîte mail (et les spams).');
            return $this->redirectToRoute('app_forgot_password');
        }

        return $this->render('front/auth/forgot_password.html.twig');
    }

    /**
     * Étape 2 : formulaire de nouveau mot de passe (via le lien dans le mail)
     */
    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function resetPassword(
        string $token,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = $userRepository->findOneBy(['resetToken' => $token]);

        if (!$user || !$user->isResetTokenValid()) {
            $this->addFlash('error', 'Ce lien de réinitialisation est invalide ou expiré. Veuillez recommencer.');
            return $this->redirectToRoute('app_forgot_password');
        }

        if ($request->isMethod('POST')) {
            $password  = (string) $request->request->get('password', '');
            $confirm   = (string) $request->request->get('password_confirm', '');

            if (strlen($password) < 8) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins 8 caractères.');
                return $this->redirectToRoute('app_reset_password', ['token' => $token]);
            }

            if ($password !== $confirm) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
                return $this->redirectToRoute('app_reset_password', ['token' => $token]);
            }

            // Hash et sauvegarde
            $user->setPassword($passwordHasher->hashPassword($user, $password));
            $user->setResetToken(null);
            $user->setResetTokenExpiresAt(null);
            $em->flush();

            $this->addFlash('success', 'Mot de passe réinitialisé avec succès ! Vous pouvez maintenant vous connecter.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('front/auth/reset_password.html.twig', ['token' => $token]);
    }
}
