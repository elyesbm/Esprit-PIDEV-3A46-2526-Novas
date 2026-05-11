<?php

namespace App\Controller\Front;

use App\Repository\AtelierRepository;
use App\Repository\ReservationRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/ateliers')]
class AtelierController extends AbstractController
{
    #[Route('', name: 'app_ateliers')]
    #[Route('', name: 'app_reservation_ateliers')]
    public function index(
        Request $request,
        AtelierRepository $atelierRepository,
        ReservationRepository $reservationRepository,
        PaginatorInterface $paginator
    ): Response
    {
        $searchParam = $request->query->get('search');
        $search = is_string($searchParam) && trim($searchParam) !== '' ? trim($searchParam) : null;
        $contexte = $request->query->get('contexte');
        $sortParam = $request->query->get('sort');
        $sort = is_string($sortParam) && trim($sortParam) !== '' ? trim($sortParam) : 'date_asc';

        $contexteInt = null;
        if ($contexte !== null && $contexte !== '') {
            $contexteInt = (int) $contexte;
            if ($contexteInt !== 0 && $contexteInt !== 1) {
                $contexteInt = null;
            }
        }

        $validSorts = ['date_asc', 'date_desc', 'titre_asc', 'titre_desc'];
        if (!in_array($sort, $validSorts, true)) {
            $sort = 'date_asc';
        }

        $topAtelierRow = $reservationRepository->findTopAtelierByReservations();
        $hasFilter = $search !== null || $contexteInt !== null;
        $shouldPinTop = $sortParam === null && ! $hasFilter;
        $pinAtelierId = $shouldPinTop && isset($topAtelierRow['atelier'])
            ? $topAtelierRow['atelier']->getId()
            : null;

        $queryBuilder = $atelierRepository->searchAndFilterQueryBuilder($search, $contexteInt, $sort, $pinAtelierId);
        $page = max(1, (int) $request->query->get('page', 1));
        $ateliers = $paginator->paginate($queryBuilder, $page, 3);

        return $this->render('front/reservation/ateliers.html.twig', [
            'ateliers' => $ateliers,
            'search' => $search ?? '',
            'contexte' => $contexteInt,
            'sort' => $sort,
            'topAtelier' => $topAtelierRow['atelier'] ?? null,
            'topAtelierCount' => $topAtelierRow['total'] ?? 0,
        ]);
    }
}
