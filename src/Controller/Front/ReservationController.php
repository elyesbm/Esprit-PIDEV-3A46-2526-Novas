<?php

namespace App\Controller\Front;

use App\Entity\Atelier;
use App\Entity\Reservation;
use App\Entity\User;
use App\Form\ReservationType;
use App\Repository\AtelierRepository;
use App\Repository\ReservationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/reservation')]
class ReservationController extends AbstractController
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    private const OTP_SESSION_KEY = 'reservation_otp';
    private const OTP_TTL_SECONDS = 600;
    private const OTP_MAX_ATTEMPTS = 5;

    #[Route('/mes-reservations', name: 'app_reservation_mes', methods: ['GET'])]
    #[Route('/mes-reservations/{id}', name: 'app_reservation_mes_legacy', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function mesReservations(
        Request $request,
        ReservationRepository $reservationRepository,
        UserRepository $userRepository,
        ?int $id = null
    ): Response {
        $user = $this->resolveUser($userRepository);
        if ($id !== null && $user->getId() !== $id) {
            throw $this->createAccessDeniedException('Vous ne pouvez consulter que vos reservations.');
        }

        $searchRaw = $request->query->get('search');
        $search = is_string($searchRaw) && trim($searchRaw) !== '' ? trim($searchRaw) : null;
        $statut = $request->query->get('statut');

        $statutInt = null;
        if ($statut !== null && $statut !== '') {
            $candidate = (int) $statut;
            if (in_array($candidate, [0, 1, 2], true)) {
                $statutInt = $candidate;
            }
        }

        $all = $reservationRepository->searchAndFilterByUser($user, $search, $statutInt);

        $today = new \DateTimeImmutable('today');
        $reservations = [];
        $historique = [];

        foreach ($all as $reservation) {
            $atelier = $reservation->getAtelier();
            if (!$atelier || !$atelier->getDateAtelier()) {
                continue;
            }

            $dateAtelier = \DateTimeImmutable::createFromInterface($atelier->getDateAtelier());
            if ($dateAtelier >= $today) {
                $reservations[] = $reservation;
                continue;
            }

            $historique[] = $reservation;
        }

        return $this->render('front/reservation/mes_reservations.html.twig', [
            'user' => $user,
            'reservations' => $reservations,
            'historique' => $historique,
            'search' => $search ?? '',
            'statut' => $statutInt,
        ]);
    }

    #[Route('/{id}/reserver', name: 'app_reservation_reserver', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    #[Route('/{id}/reserver/{userId}', name: 'app_reservation_reserver_legacy', requirements: ['id' => '\\d+', 'userId' => '\\d+'], methods: ['GET', 'POST'])]
    public function reserver(
        Request $request,
        AtelierRepository $atelierRepository,
        ReservationRepository $reservationRepository,
        UserRepository $userRepository,
        MailerInterface $mailer,
        EntityManagerInterface $entityManager,
        int $id,
        ?int $userId = null
    ): Response {
        $atelier = $atelierRepository->find($id);
        if (!$atelier) {
            throw $this->createNotFoundException('Atelier introuvable.');
        }

        $user = $this->resolveUser($userRepository);
        if ($userId !== null && $user->getId() !== $userId) {
            throw $this->createAccessDeniedException('Vous ne pouvez reserver que pour votre propre compte.');
        }

        if ($reservationRepository->existsForUserAndAtelier($user, $atelier)) {
            $this->addFlash('warning', 'Vous avez deja une reservation pour cet atelier.');
            return $this->redirectToRoute('app_reservation_mes');
        }

        $reservation = new Reservation();
        $reservation->setAtelier($atelier);
        $reservation->setUser($user);
        $reservation->setStatutReservation(0);

        $fullName = trim(sprintf('%s %s', (string) $user->getPrenom(), (string) $user->getNom()));
        $reservation->setNomUser($fullName !== '' ? $fullName : ((string) $user->getNom() ?: 'Utilisateur'));
        $reservation->setEmailUser((string) ($user->getEmail() ?? ''));

        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $code = (string) random_int(100000, 999999);
            $payload = [
                'atelier_id' => $atelier->getId(),
                'user_id' => $user->getId(),
                'nom_user' => (string) $reservation->getNomUser(),
                'email_user' => (string) $reservation->getEmailUser(),
                'commentaire_reservation' => $reservation->getCommentaireReservation(),
            ];

            if (!$this->sendReservationOtpEmail($mailer, $payload['email_user'], $payload['nom_user'], $atelier, $code)) {
                $this->addFlash(
                    'error',
                    'Impossible d\'envoyer l\'email de verification (service mail indisponible ou IP non autorisee sur Brevo). '
                    . 'Consultez https://app.brevo.com/security/authorised_ips ou utilisez MAILER_DSN=null://null en developpement dans .env.local.'
                );

                return $this->render('front/reservation/reserver.html.twig', [
                    'atelier' => $atelier,
                    'form' => $form->createView(),
                ]);
            }

            $request->getSession()->set(self::OTP_SESSION_KEY, [
                'code_hash' => hash('sha256', $code),
                'expires_at' => time() + self::OTP_TTL_SECONDS,
                'attempts' => 0,
                'payload' => $payload,
            ]);

            $this->addFlash('info', 'Un code de verification a ete envoye par email.');
            return $this->redirectToRoute('app_reservation_verify_code');
        }

        return $this->render('front/reservation/reserver.html.twig', [
            'atelier' => $atelier,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/verify-code', name: 'app_reservation_verify_code', methods: ['GET', 'POST'])]
    public function verifyCode(
        Request $request,
        AtelierRepository $atelierRepository,
        ReservationRepository $reservationRepository,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $session = $request->getSession();
        $data = $session->get(self::OTP_SESSION_KEY);

        if (!is_array($data) || !isset($data['payload'])) {
            $this->addFlash('warning', 'Aucune verification en cours.');
            return $this->redirectToRoute('app_reservation_ateliers');
        }

        $payload = $data['payload'];
        $expiresAt = (int) ($data['expires_at'] ?? 0);
        $attempts = (int) ($data['attempts'] ?? 0);

        if ($expiresAt > 0 && time() > $expiresAt) {
            $session->remove(self::OTP_SESSION_KEY);
            $this->addFlash('error', 'Le code a expire. Merci de recommencer la reservation.');
            return $this->redirectToReservationStart($payload);
        }

        if ($attempts >= self::OTP_MAX_ATTEMPTS) {
            $session->remove(self::OTP_SESSION_KEY);
            $this->addFlash('error', 'Nombre de tentatives depasse. Merci de recommencer.');
            return $this->redirectToReservationStart($payload);
        }

        if ($request->isMethod('POST')) {
            $token = (string) $request->request->get('_token');
            if (!$this->isCsrfTokenValid('reservation_otp_verify', $token)) {
                throw $this->createAccessDeniedException('Token CSRF invalide.');
            }

            $code = trim((string) $request->request->get('otp_code', ''));
            if (!preg_match('/^\\d{6}$/', $code)) {
                $this->addFlash('error', 'Le code doit contenir 6 chiffres.');
            } elseif (hash('sha256', $code) !== ($data['code_hash'] ?? '')) {
                $data['attempts'] = $attempts + 1;
                $session->set(self::OTP_SESSION_KEY, $data);
                $this->addFlash('error', 'Code incorrect.');
            } else {
                $user = $this->resolveUser($userRepository);
                $payloadUserId = (int) ($payload['user_id'] ?? 0);
                if ($payloadUserId !== 0 && $payloadUserId !== $user->getId()) {
                    throw $this->createAccessDeniedException('Utilisateur invalide.');
                }

                $atelierId = (int) ($payload['atelier_id'] ?? 0);
                $atelier = $atelierRepository->find($atelierId);
                if (!$atelier) {
                    $session->remove(self::OTP_SESSION_KEY);
                    $this->addFlash('error', 'Atelier introuvable.');
                    return $this->redirectToRoute('app_reservation_ateliers');
                }

                if ($reservationRepository->existsForUserAndAtelier($user, $atelier)) {
                    $session->remove(self::OTP_SESSION_KEY);
                    $this->addFlash('warning', 'Vous avez deja une reservation pour cet atelier.');
                    return $this->redirectToRoute('app_reservation_mes');
                }

                $reservation = new Reservation();
                $reservation->setAtelier($atelier);
                $reservation->setUser($user);
                $reservation->setStatutReservation(0);
                $reservation->setNomUser((string) ($payload['nom_user'] ?? 'Utilisateur'));
                $reservation->setEmailUser((string) ($payload['email_user'] ?? ''));
                $reservation->setCommentaireReservation($payload['commentaire_reservation'] ?? null);

                $entityManager->persist($reservation);
                $entityManager->flush();

                $session->remove(self::OTP_SESSION_KEY);
                $this->addFlash('success', 'Votre reservation a ete confirmee.');
                return $this->redirectToRoute('app_reservation_mes');
            }
        }

        $minutesLeft = 0;
        if ($expiresAt > 0) {
            $remaining = max(0, $expiresAt - time());
            $minutesLeft = (int) ceil($remaining / 60);
        }

        return $this->render('front/reservation/verify_code.html.twig', [
            'email' => $payload['email_user'] ?? '',
            'minutes_left' => $minutesLeft,
        ]);
    }

    #[Route('/verify-code/resend', name: 'app_reservation_resend_code', methods: ['POST'])]
    public function resendCode(
        Request $request,
        MailerInterface $mailer,
        AtelierRepository $atelierRepository
    ): Response {
        $token = (string) $request->request->get('_token');
        if (!$this->isCsrfTokenValid('reservation_otp_resend', $token)) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $session = $request->getSession();
        $data = $session->get(self::OTP_SESSION_KEY);
        if (!is_array($data) || !isset($data['payload'])) {
            $this->addFlash('warning', 'Aucune verification en cours.');
            return $this->redirectToRoute('app_reservation_ateliers');
        }

        $payload = $data['payload'];
        $code = (string) random_int(100000, 999999);
        $previousData = $data;
        $data['code_hash'] = hash('sha256', $code);
        $data['expires_at'] = time() + self::OTP_TTL_SECONDS;
        $data['attempts'] = 0;
        $session->set(self::OTP_SESSION_KEY, $data);

        $atelierId = (int) ($payload['atelier_id'] ?? 0);
        $atelier = $atelierId > 0 ? $atelierRepository->find($atelierId) : null;
        if (!$this->sendReservationOtpEmail(
            $mailer,
            (string) ($payload['email_user'] ?? ''),
            (string) ($payload['nom_user'] ?? 'Utilisateur'),
            $atelier,
            $code
        )) {
            $session->set(self::OTP_SESSION_KEY, $previousData);
            $this->addFlash(
                'error',
                'Impossible d\'envoyer l\'email (Brevo : IP non autorisee ou service indisponible). L\'ancien code reste valide si vous l\'avez encore.'
            );

            return $this->redirectToRoute('app_reservation_verify_code');
        }

        $this->addFlash('success', 'Un nouveau code a ete envoye.');
        return $this->redirectToRoute('app_reservation_verify_code');
    }

    #[Route('/export-pdf/{id}', name: 'app_reservation_export_pdf', requirements: ['id' => '\\d+'])]
    public function exportPdf(int $id, ReservationRepository $reservationRepository, UserRepository $userRepository): Response
    {
        $reservation = $reservationRepository->find($id);
        if (!$reservation) {
            throw $this->createNotFoundException('Reservation introuvable.');
        }
        $this->assertReservationOwner($reservation, $this->resolveUser($userRepository));

        $html = $this->renderView('front/reservation/pdf_reservation.html.twig', [
            'reservation' => $reservation,
        ]);

        if (class_exists(\Dompdf\Dompdf::class)) {
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->getOptions()->set('isRemoteEnabled', true);
            $dompdf->getOptions()->set('defaultFont', 'DejaVu Sans');
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            return new Response($dompdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="reservation-' . $reservation->getId() . '.pdf"',
            ]);
        }

        $printHtml = $this->renderView('front/reservation/pdf_reservation_print.html.twig', [
            'reservation' => $reservation,
            'content' => $html,
        ]);

        return new Response($printHtml, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    #[Route('/annuler/{id}', name: 'app_reservation_annuler', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function annuler(
        int $id,
        Request $request,
        ReservationRepository $reservationRepository,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository
    ): Response {
        $reservation = $reservationRepository->find($id);
        if (!$reservation) {
            throw $this->createNotFoundException('Reservation introuvable.');
        }
        $this->assertReservationOwner($reservation, $this->resolveUser($userRepository));

        $token = (string) $request->request->get('_token');
        if (!$this->isCsrfTokenValid('annuler' . $id, $token)) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $entityManager->remove($reservation);
        $entityManager->flush();

        $this->addFlash('success', 'Reservation annulee.');
        return $this->redirectToRoute('app_reservation_mes');
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function redirectToReservationStart(array $payload): Response
    {
        $atelierId = (int) ($payload['atelier_id'] ?? 0);
        if ($atelierId > 0) {
            return $this->redirectToRoute('app_reservation_reserver', ['id' => $atelierId]);
        }

        return $this->redirectToRoute('app_reservation_ateliers');
    }

    /**
     * @return bool true si l'email a ete accepte par le transporteur, false sinon (sans lever d'exception).
     */
    private function sendReservationOtpEmail(
        MailerInterface $mailer,
        string $toEmail,
        string $toName,
        ?Atelier $atelier,
        string $code
    ): bool {
        if ($toEmail === '') {
            return true;
        }

        $fromRaw = $_ENV['MAILER_FROM'] ?? $_SERVER['MAILER_FROM'] ?? (getenv('MAILER_FROM') ?: null);
        $from = $fromRaw ? Address::create($fromRaw) : new Address('no-reply@example.com', 'NOVAS');
        $subject = $atelier ? 'Code de confirmation - ' . $atelier->getTitreAtelier() : 'Code de confirmation';

        $text = "Bonjour {$toName},\n\n".
            "Votre code de confirmation est : {$code}\n".
            "Ce code est valable 10 minutes.\n\n".
            "Si vous n'etes pas a l'origine de cette demande, ignorez cet email.\n";

        $html = '<p>Bonjour ' . htmlspecialchars($toName) . ',</p>'
            . '<p>Votre code de confirmation est :</p>'
            . '<p style="font-size:24px;font-weight:bold;letter-spacing:4px;">' . htmlspecialchars($code) . '</p>'
            . '<p>Ce code est valable 10 minutes.</p>'
            . "<p>Si vous n'etes pas a l'origine de cette demande, ignorez cet email.</p>";

        $email = (new Email())
            ->from($from)
            ->to(new Address($toEmail, $toName))
            ->subject($subject)
            ->text($text)
            ->html($html);

        try {
            $mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $this->logger->warning('Envoi email reservation OTP echoue: {message}', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);

            return false;
        }

        return true;
    }

    private function resolveUser(UserRepository $userRepository): User
    {
        $securityUser = $this->getUser();
        if ($securityUser instanceof User) {
            return $securityUser;
        }

        if ($securityUser !== null) {
            $identifier = $securityUser->getUserIdentifier();
            if ($identifier !== '') {
                $user = $userRepository->findOneBy(['email' => $identifier]);
                if ($user instanceof User) {
                    return $user;
                }
            }
        }

        throw $this->createAccessDeniedException('Authentication required.');
    }

    private function assertReservationOwner(Reservation $reservation, User $user): void
    {
        $ownerId = $reservation->getUser()?->getId();
        if ($ownerId === null || $ownerId !== $user->getId()) {
            throw $this->createAccessDeniedException('Acces refuse a cette reservation.');
        }
    }
}
