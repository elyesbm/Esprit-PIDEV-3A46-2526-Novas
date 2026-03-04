<?php

namespace App\Controller\Admin;

use App\Repository\ArticleRepository;
use App\Repository\AtelierRepository;
use App\Repository\OffrejobRepository;
use App\Repository\UserRepository;
use App\Repository\ReservationRepository;
use App\Repository\PublicationRepository;
use App\Repository\CommandeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Security;

#[Route('/admin', name: 'app_admin_')]
class DashboardController extends AbstractController
{
    public function __construct(private Security $security) {}

    #[Route('/', name: 'dashboard')]
    public function index(
        UserRepository        $userRepo,
        ArticleRepository     $articleRepo,
        OffrejobRepository    $offreRepo,
        AtelierRepository     $atelierRepo,
        ReservationRepository $reservationRepo,
        PublicationRepository $publicationRepo,
        CommandeRepository    $commandeRepo,
    ): Response {
        $user = $this->security->getUser();

        // ── Statistiques globales (ROLE_ADMIN) ────────────────────────────
        $totalUtilisateurs          = $userRepo->count([]);
        $totalArticles              = $articleRepo->count([]);
        $totalOffres                = $offreRepo->count([]);
        $totalAteliers              = $atelierRepo->count([]);
        // Publications en attente de modération (statut = 0 = non validé)
        $totalPublicationsSignalees = $publicationRepo->count(['statut' => 0]);

        // ── Activité récente ───────────────────────────────────────────────
        // 3 derniers utilisateurs inscrits
        $derniersUtilisateurs = $userRepo->findBy([], ['id' => 'DESC'], 3);

        // 3 derniers articles publiés
        $derniersArticles = $articleRepo->findBy([], ['id' => 'DESC'], 3);

        // 3 dernières offres d'emploi créées
        $dernieresOffres = $offreRepo->findBy([], ['id' => 'DESC'], 3);

        // 3 dernières réservations
        $dernieresReservations = $reservationRepo->findBy([], ['id' => 'DESC'], 3);

        // ── Chiffre d'affaires total (Commandes) ──────────────────────────
        $chiffreAffaires = $commandeRepo->createQueryBuilder('c')
            ->select('SUM(c.montant)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        return $this->render('admin/dashboard.html.twig', [
            'user'                      => $user,
            // Stats
            'totalUtilisateurs'         => $totalUtilisateurs,
            'totalArticles'             => $totalArticles,
            'totalOffres'               => $totalOffres,
            'totalAteliers'             => $totalAteliers,
            'chiffreAffaires'           => $chiffreAffaires,
            'totalPublicationsSignalees'=> $totalPublicationsSignalees,
            // Activité récente
            'derniersUtilisateurs'      => $derniersUtilisateurs,
            'derniersArticles'          => $derniersArticles,
            'dernieresOffres'           => $dernieresOffres,
            'dernieresReservations'     => $dernieresReservations,
        ]);
    }
}
