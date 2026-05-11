<?php

namespace App\Controller\Admin;

use App\Exception\AiApiException;
use App\Entity\Offrejob;
use App\Entity\User;
use App\Enum\ModerationStatus;
use App\Enum\OffreStatut;
use App\Repository\CandidatureJobRepository;
use App\Repository\OffrejobRepository;
use App\Service\AiModerationService;
use App\Service\JobStatsService;
use App\Service\OffreQuotaManager;
use Doctrine\ORM\EntityManagerInterface;
use Nucleos\DompdfBundle\Wrapper\DompdfWrapperInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

#[Route('/admin/jobs')]
class JobsAdminController extends AbstractController
{
    #[Route('', name: 'app_admin_jobs_list', methods: ['GET'])]
    public function list(Request $request, OffrejobRepository $repo): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $q = trim((string) $request->query->get('q', ''));
        $sort = (string) $request->query->get('sort', 'date');
        $dir = (string) $request->query->get('dir', 'desc');

        $offres = $repo->searchAndSort($q ?: null, null, null, null, $sort, $dir);

        return $this->render('admin/jobs/list.html.twig', [
            'offres' => $offres,
            'q' => $q,
            'sort' => $sort,
            'dir' => $dir,
        ]);
    }

    #[Route('/stats', name: 'app_admin_jobs_stats', methods: ['GET'])]
    public function stats(JobStatsService $jobStatsService, ChartBuilderInterface $chartBuilder): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $offersPerCategory = $jobStatsService->getOffersPerCategory();
        $applicationsPerDay = $jobStatsService->getApplicationsPerDayLastDays(30);
        $applicationsByStatus = $jobStatsService->getApplicationsByStatus();
        $summary = $jobStatsService->getSummary(30);

        $offersByCategoryChart = $chartBuilder->createChart(Chart::TYPE_BAR);
        $offersByCategoryChart->setData([
            'labels' => $offersPerCategory['labels'],
            'datasets' => [[
                'label' => 'Offres par categorie',
                'data' => $offersPerCategory['data'],
                'backgroundColor' => ['#0ea5e9', '#22c55e', '#f59e0b', '#8b5cf6'],
                'borderRadius' => 8,
            ]],
        ]);
        $offersByCategoryChart->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => ['legend' => ['display' => false]],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => ['precision' => 0],
                ],
            ],
        ]);

        $applicationsPerDayChart = $chartBuilder->createChart(Chart::TYPE_LINE);
        $applicationsPerDayChart->setData([
            'labels' => $applicationsPerDay['labels'],
            'datasets' => [[
                'label' => 'Candidatures / jour',
                'data' => $applicationsPerDay['data'],
                'borderColor' => '#0ea5e9',
                'backgroundColor' => 'rgba(14,165,233,0.18)',
                'fill' => true,
                'tension' => 0.35,
                'pointRadius' => 2,
            ]],
        ]);
        $applicationsPerDayChart->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => ['legend' => ['display' => false]],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => ['precision' => 0],
                ],
            ],
        ]);

        $applicationsByStatusChart = $chartBuilder->createChart(Chart::TYPE_PIE);
        $applicationsByStatusChart->setData([
            'labels' => $applicationsByStatus['labels'],
            'datasets' => [[
                'data' => $applicationsByStatus['data'],
                'backgroundColor' => ['#f59e0b', '#22c55e', '#ef4444'],
                'borderColor' => '#ffffff',
                'borderWidth' => 2,
            ]],
        ]);
        $applicationsByStatusChart->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => [
                        'boxWidth' => 14,
                    ],
                ],
            ],
        ]);

        return $this->render('admin/jobs/stats.html.twig', [
            'offersByCategoryChart' => $offersByCategoryChart,
            'applicationsPerDayChart' => $applicationsPerDayChart,
            'applicationsByStatusChart' => $applicationsByStatusChart,
            'summary' => $summary,
        ]);
    }

    #[Route('/export-pdf', name: 'app_admin_jobs_export_pdf', methods: ['GET'])]
    public function exportAllPdf(OffrejobRepository $repo, DompdfWrapperInterface $dompdf): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $offres = $repo->findBy([], ['date_creation_offre' => 'DESC']);

        $html = $this->renderView('pdf/jobs/admin_offers.html.twig', [
            'offres' => $offres,
            'generatedAt' => new \DateTimeImmutable(),
        ]);

        $pdf = $dompdf->getPdf($html, [
            'defaultPaperSize' => 'a4',
            'defaultPaperOrientation' => 'portrait',
        ]);

        $response = new Response($pdf);
        $disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'admin-jobs.pdf');
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    #[Route('/new', name: 'app_admin_jobs_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, AiModerationService $moderationService): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $offre = new Offrejob();

        if ($request->isMethod('POST')) {
            if (! $this->isCsrfTokenValid('admin_jobs_form_create', (string) $request->request->get('_token'))) {
                $this->addFlash('danger', 'Jeton CSRF invalide.');
                return $this->redirectToRoute('app_admin_jobs_new');
            }

            $categorie = (string) $request->request->get('categorie_offre', 'TUTORAT');
            $lieu = (string) $request->request->get('lieu', 'EN_LIGNE');
            $adresse = trim((string) $request->request->get('adresse', ''));
            $latitude = $this->parseNullableFloat($request->request->get('latitude'));
            $longitude = $this->parseNullableFloat($request->request->get('longitude'));
            $statut = (string) ($request->request->get('statut_offre') ?: OffreStatut::OUVERTE->value);
            $capaciteMax = (int) $request->request->get('capacite_max', 5);
            $dateExpiration = $this->parseDateExpiration((string) $request->request->get('date_expiration', ''));

            if (!in_array($categorie, ['TUTORAT', 'AIDE', 'CREATION'], true)) {
                $categorie = 'TUTORAT';
            }
            if (!in_array($lieu, ['EN_LIGNE', 'PRESENTIEL'], true)) {
                $lieu = 'EN_LIGNE';
            }
            if (!in_array($statut, [OffreStatut::OUVERTE->value, OffreStatut::FERMEE->value, OffreStatut::EXPIREE->value], true)) {
                $statut = OffreStatut::OUVERTE->value;
            }
            if ($capaciteMax <= 0) {
                $this->addFlash('danger', 'La capacite maximale doit etre superieure a 0.');
                return $this->redirectToRoute('app_admin_jobs_new');
            }
            if ($lieu === 'PRESENTIEL') {
                if ($adresse === '' || mb_strlen($adresse) < 3) {
                    $this->addFlash('danger', 'L adresse est obligatoire pour une offre en presentiel.');
                    return $this->redirectToRoute('app_admin_jobs_new');
                }
                if ($latitude === null || $longitude === null) {
                    $this->addFlash('danger', 'Veuillez localiser l adresse pour recuperer les coordonnees.');
                    return $this->redirectToRoute('app_admin_jobs_new');
                }
            }
            if (! $dateExpiration || $dateExpiration <= new \DateTimeImmutable()) {
                $this->addFlash('danger', 'La date d expiration doit etre dans le futur.');
                return $this->redirectToRoute('app_admin_jobs_new');
            }

            $moderationText = sprintf(
                "Titre:\n%s\n\nDescription:\n%s",
                (string) $request->request->get('titre_offre'),
                (string) $request->request->get('description_offre')
            );

            try {
                $moderation = $moderationService->moderateText($moderationText);
            } catch (AiApiException|\RuntimeException) {
                $this->addFlash('danger', 'La moderation automatique est indisponible. Reessayez dans quelques instants.');
                return $this->redirectToRoute('app_admin_jobs_new');
            }

            $moderationAction = $moderation['action'];
            $moderationReasons = $moderation['reasons'];

            if ($moderationAction === 'BLOCK') {
                $this->addFlash('danger', 'Offre bloquee par moderation: ' . implode(', ', $moderationReasons));
                return $this->redirectToRoute('app_admin_jobs_new');
            }

            $offre->setTitreOffre((string) $request->request->get('titre_offre'));
            $offre->setDescriptionOffre((string) $request->request->get('description_offre'));
            $offre->setCategorieOffre($categorie);
            $offre->setLieu($lieu);
            $offre->setAdresse($lieu === 'PRESENTIEL' ? $adresse : null);
            $offre->setLatitude($lieu === 'PRESENTIEL' ? $latitude : null);
            $offre->setLongitude($lieu === 'PRESENTIEL' ? $longitude : null);
            $offre->setStatutOffre($statut);
            $offre->setCapaciteMax($capaciteMax);
            $offre->setCapaciteRestante($capaciteMax);
            $offre->setDateCreationOffre(new \DateTime());
            $offre->setDateExpiration($dateExpiration);
            $createur = $this->getUser();
            $offre->setCreateur($createur instanceof User ? $createur : null);
            $offre->setModerationStatus(
                $moderationAction === 'ALLOW'
                    ? ModerationStatus::APPROVED
                    : ModerationStatus::PENDING
            );
            if ($moderationAction === 'FLAG') {
                $this->addFlash('warning', 'Offre en attente de moderation.');
            }

            $em->persist($offre);
            $em->flush();

            $this->addFlash('success', 'Offre creee.');
            return $this->redirectToRoute('app_admin_jobs_list');
        }

        return $this->render('admin/jobs/form.html.twig', [
            'mode' => 'create',
            'offre' => $offre,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_jobs_edit', methods: ['GET', 'POST'], requirements: ['id' => '\\d+'])]
    public function edit(Offrejob $offre, Request $request, EntityManagerInterface $em, AiModerationService $moderationService): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if ($request->isMethod('POST')) {
            if (! $this->isCsrfTokenValid('admin_jobs_form_edit_' . $offre->getId(), (string) $request->request->get('_token'))) {
                $this->addFlash('danger', 'Jeton CSRF invalide.');
                return $this->redirectToRoute('app_admin_jobs_edit', ['id' => $offre->getId()]);
            }

            $categorie = (string) $request->request->get('categorie_offre', $offre->getCategorieOffre());
            $lieu = (string) $request->request->get('lieu', $offre->getLieu());
            $adresse = trim((string) $request->request->get('adresse', (string) ($offre->getAdresse() ?? '')));
            $latitude = $this->parseNullableFloat($request->request->get('latitude', $offre->getLatitude()));
            $longitude = $this->parseNullableFloat($request->request->get('longitude', $offre->getLongitude()));
            $statut = (string) ($request->request->get('statut_offre') ?: $offre->getStatutOffre());
            $capaciteMax = (int) $request->request->get('capacite_max', $offre->getCapaciteMax());
            $dateExpiration = $this->parseDateExpiration((string) $request->request->get('date_expiration', $offre->getDateExpiration()?->format('Y-m-d\TH:i')));

            if (!in_array($categorie, ['TUTORAT', 'AIDE', 'CREATION'], true)) {
                $categorie = $offre->getCategorieOffre() ?: 'TUTORAT';
            }
            if (!in_array($lieu, ['EN_LIGNE', 'PRESENTIEL'], true)) {
                $lieu = $offre->getLieu() ?: 'EN_LIGNE';
            }
            if (!in_array($statut, [OffreStatut::OUVERTE->value, OffreStatut::FERMEE->value, OffreStatut::EXPIREE->value], true)) {
                $statut = $offre->getStatutOffre() ?: OffreStatut::OUVERTE->value;
            }
            if ($capaciteMax <= 0) {
                $this->addFlash('danger', 'La capacite maximale doit etre superieure a 0.');
                return $this->redirectToRoute('app_admin_jobs_edit', ['id' => $offre->getId()]);
            }
            if ($lieu === 'PRESENTIEL') {
                if ($adresse === '' || mb_strlen($adresse) < 3) {
                    $this->addFlash('danger', 'L adresse est obligatoire pour une offre en presentiel.');
                    return $this->redirectToRoute('app_admin_jobs_edit', ['id' => $offre->getId()]);
                }
                if ($latitude === null || $longitude === null) {
                    $this->addFlash('danger', 'Veuillez localiser l adresse pour recuperer les coordonnees.');
                    return $this->redirectToRoute('app_admin_jobs_edit', ['id' => $offre->getId()]);
                }
            }
            if (! $dateExpiration || $dateExpiration <= new \DateTimeImmutable()) {
                $this->addFlash('danger', 'La date d expiration doit etre dans le futur.');
                return $this->redirectToRoute('app_admin_jobs_edit', ['id' => $offre->getId()]);
            }

            $acceptedCount = $this->countAcceptedCandidatures($offre);
            if ($capaciteMax < $acceptedCount) {
                $this->addFlash('danger', sprintf('La capacite maximale doit etre >= au nombre de candidatures acceptees (%d).', $acceptedCount));
                return $this->redirectToRoute('app_admin_jobs_edit', ['id' => $offre->getId()]);
            }

            $moderationText = sprintf(
                "Titre:\n%s\n\nDescription:\n%s",
                (string) $request->request->get('titre_offre'),
                (string) $request->request->get('description_offre')
            );

            try {
                $moderation = $moderationService->moderateText($moderationText);
            } catch (AiApiException|\RuntimeException) {
                $this->addFlash('danger', 'La moderation automatique est indisponible. Reessayez dans quelques instants.');
                return $this->redirectToRoute('app_admin_jobs_edit', ['id' => $offre->getId()]);
            }

            $moderationAction = $moderation['action'];
            $moderationReasons = $moderation['reasons'];

            if ($moderationAction === 'BLOCK') {
                $offre->setModerationStatus(ModerationStatus::REJECTED);
                $em->flush();
                $this->addFlash('danger', 'Offre bloquee par moderation: ' . implode(', ', $moderationReasons));
                return $this->redirectToRoute('app_admin_jobs_edit', ['id' => $offre->getId()]);
            }

            $offre->setTitreOffre((string) $request->request->get('titre_offre'));
            $offre->setDescriptionOffre((string) $request->request->get('description_offre'));
            $offre->setCategorieOffre($categorie);
            $offre->setLieu($lieu);
            $offre->setAdresse($lieu === 'PRESENTIEL' ? $adresse : null);
            $offre->setLatitude($lieu === 'PRESENTIEL' ? $latitude : null);
            $offre->setLongitude($lieu === 'PRESENTIEL' ? $longitude : null);
            $offre->setCapaciteMax($capaciteMax);
            $offre->setDateExpiration($dateExpiration);

            $remaining = max(0, $capaciteMax - $acceptedCount);
            $offre->setCapaciteRestante($remaining);
            if ($remaining === 0 && $statut === OffreStatut::OUVERTE->value) {
                $statut = OffreStatut::FERMEE->value;
            }
            $offre->setStatutOffre($statut);
            $offre->setModerationStatus(
                $moderationAction === 'ALLOW'
                    ? ModerationStatus::APPROVED
                    : ModerationStatus::PENDING
            );
            if ($moderationAction === 'FLAG') {
                $this->addFlash('warning', 'Offre en attente de moderation apres modification.');
            }

            $em->flush();

            $this->addFlash('success', 'Offre modifiee.');
            return $this->redirectToRoute('app_admin_jobs_list');
        }

        return $this->render('admin/jobs/form.html.twig', [
            'mode' => 'edit',
            'offre' => $offre,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_jobs_delete', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function delete(Offrejob $offre, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if (! $this->isCsrfTokenValid('admin_jobs_delete_' . $offre->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_admin_jobs_list');
        }

        $em->remove($offre);
        $em->flush();

        $this->addFlash('success', 'Offre supprimee.');
        return $this->redirectToRoute('app_admin_jobs_list');
    }

    #[Route('/{id}/candidatures', name: 'app_admin_jobs_candidatures')]
    public function candidatures(
        Offrejob $offre,
        CandidatureJobRepository $repo
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $cands = $repo->findBy(
            ['offre' => $offre],
            ['date_candidature' => 'DESC']
        );

        $stats = [
            'total' => count($cands),
            'enAttente' => 0,
            'acceptees' => 0,
            'refusees' => 0,
        ];

        foreach ($cands as $c) {
            $st = $c->getStatutCandidature();
            if ($st === 'EN_ATTENTE') {
                $stats['enAttente']++;
            } elseif ($st === 'ACCEPTEE') {
                $stats['acceptees']++;
            } elseif ($st === 'REFUSEE') {
                $stats['refusees']++;
            }
        }

        return $this->render('front/jobs/mes_candidatures.html.twig', [
            'candidatures' => $cands,
            'stats' => $stats,
            'admin_view' => true,
            'can_manage' => true,
            'manage_route' => 'app_admin_jobs_candidature_status',
            'offre' => $offre,
        ]);
    }

    #[Route('/candidature/{id}/status/{status}', name: 'app_admin_jobs_candidature_status', methods: ['POST'], requirements: ['id' => '\\d+', 'status' => 'ACCEPTEE|REFUSEE'])]
    public function setCandidatureStatus(
        int $id,
        string $status,
        Request $request,
        CandidatureJobRepository $candRepo,
        OffreQuotaManager $quotaManager
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $cand = $candRepo->find($id);
        if (!$cand) {
            throw $this->createNotFoundException();
        }

        if (! $this->isCsrfTokenValid('cand_status_' . $cand->getId() . '_' . $status, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
            $offre = $cand->getOffre();
            if (! $offre instanceof Offrejob) {
                return $this->redirectToRoute('app_admin_jobs_list');
            }

            return $this->redirectToRoute('app_admin_jobs_candidatures', ['id' => $offre->getId()]);
        }

        $result = $quotaManager->applyCandidatureStatus($cand, $status);
        $this->addFlash($result['ok'] ? 'success' : 'warning', $result['message']);

        $offre = $cand->getOffre();
        if (! $offre instanceof Offrejob) {
            return $this->redirectToRoute('app_admin_jobs_list');
        }

        return $this->redirectToRoute('app_admin_jobs_candidatures', ['id' => $offre->getId()]);
    }

    private function countAcceptedCandidatures(Offrejob $offre): int
    {
        $count = 0;
        foreach ($offre->getCandidatures() as $candidature) {
            if ($candidature->getStatutCandidature() === 'ACCEPTEE') {
                $count++;
            }
        }

        return $count;
    }

    private function parseDateExpiration(?string $value): ?\DateTimeImmutable
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseNullableFloat(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $value = trim($value);
            if ($value === '' || !is_numeric($value)) {
                return null;
            }

            return (float) $value;
        }

        if (!is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }
}
