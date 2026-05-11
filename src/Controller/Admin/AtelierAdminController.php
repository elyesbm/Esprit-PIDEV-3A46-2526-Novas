<?php

namespace App\Controller\Admin;

use App\Entity\Atelier;
use App\Form\AtelierType;
use App\Repository\AtelierRepository;
use App\Repository\ReservationRepository;
use App\Service\AiAtelierDescriptionGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/ateliers', name: 'app_admin_ateliers_')]
class AtelierAdminController extends AbstractController
{
    #[Route('/', name: 'list')]
    public function list(Request $request, AtelierRepository $atelierRepository, ReservationRepository $reservationRepository): Response
    {
        $searchRaw = $request->query->get('search');
        $contexte = $request->query->get('contexte');
        $sortRaw = $request->query->get('sort');
        $search = is_string($searchRaw) && trim($searchRaw) !== '' ? trim($searchRaw) : null;
        $sort = is_string($sortRaw) && trim($sortRaw) !== '' ? trim($sortRaw) : 'id_desc';

        // Robust contexte parsing to avoid implicit cast issues
        // (e.g. 'hard' previously became 0 with (int) cast).
        $contexteInt = match ((string) $contexte) {
            '0', 'soft' => 0,
            '1', 'hard' => 1,
            default => null,
        };

        $validSorts = ['id_desc', 'id_asc', 'date_asc', 'date_desc', 'titre_asc', 'titre_desc'];
        if (!in_array($sort, $validSorts, true)) {
            $sort = 'id_desc';
        }

        $ateliers = $atelierRepository->searchFilterAndSort($search, $contexteInt, $sort);
        $statsContexte = $atelierRepository->countByContexte();
        $nextAtelier = $atelierRepository->findNextUpcomingAtelier();
        $topAtelierRow = $reservationRepository->findTopAtelierByReservations();

        return $this->render('admin/atelier/list.html.twig', [
            'ateliers' => $ateliers,
            'search' => $search,
            'contexte' => $contexteInt,
            'sort' => $sort,
            'statsContexte' => $statsContexte,
            'nextAtelier' => $nextAtelier,
            'topAtelier' => $topAtelierRow['atelier'] ?? null,
            'topAtelierCount' => $topAtelierRow['total'] ?? 0,
            'totalAteliers' => $atelierRepository->count([]),
        ]);
    }

    #[Route('/new', name: 'new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $atelier = new Atelier();

        $form = $this->createForm(AtelierType::class, $atelier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($atelier);
            $em->flush();

            return $this->redirectToRoute('app_admin_ateliers_list');
        }

        return $this->render('admin/atelier/form.html.twig', [
            'mode' => 'create',
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\\d+'])]
    public function edit(int $id, Request $request, AtelierRepository $atelierRepository, EntityManagerInterface $em): Response
    {
        $atelier = $atelierRepository->find($id);
        if (!$atelier) {
            throw $this->createNotFoundException('Atelier introuvable.');
        }

        $form = $this->createForm(AtelierType::class, $atelier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            return $this->redirectToRoute('app_admin_ateliers_list');
        }

        return $this->render('admin/atelier/form.html.twig', [
            'mode' => 'edit',
            'form' => $form,
            'atelier' => $atelier,
        ]);
    }

    #[Route('/ai/description', name: 'ai_description', methods: ['POST'])]
    public function generateAiDescription(Request $request, AiAtelierDescriptionGenerator $generator): JsonResponse
    {
        $token = (string) ($request->headers->get('X-CSRF-TOKEN') ?? '');
        if (!$this->isCsrfTokenValid('ai_generate_atelier_description', $token)) {
            return $this->json(['error' => 'Token CSRF invalide.'], Response::HTTP_FORBIDDEN);
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json(['error' => 'Requete invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $title = trim((string) ($payload['title'] ?? ''));
        if ($title === '') {
            return $this->json(['error' => 'Le titre est obligatoire.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $description = $generator->generate($title);
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['description' => $description]);
    }

    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function delete(int $id, Request $request, AtelierRepository $atelierRepository, EntityManagerInterface $em): Response
    {
        $atelier = $atelierRepository->find($id);
        if (!$atelier) {
            throw $this->createNotFoundException('Atelier introuvable.');
        }

        $token = (string) $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete' . $id, $token)) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $em->remove($atelier);
        $em->flush();

        return $this->redirectToRoute('app_admin_ateliers_list');
    }
}
