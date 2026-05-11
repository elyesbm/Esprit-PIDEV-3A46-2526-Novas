<?php

namespace App\Controller\Admin;

use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/reservations', name: 'app_admin_reservations_')]
class ReservationAdminController extends AbstractController
{
    #[Route('/', name: 'list')]
    public function list(Request $request, ReservationRepository $reservationRepository): Response
    {
        $searchRaw = $request->query->get('search');
        $statut = $request->query->get('statut');
        $sortRaw = $request->query->get('sort');
        $search = is_string($searchRaw) && trim($searchRaw) !== '' ? trim($searchRaw) : null;
        $sort = is_string($sortRaw) && trim($sortRaw) !== '' ? trim($sortRaw) : 'id_desc';

        $statutInt = null;
        if ($statut !== null && $statut !== '') {
            $candidate = (int) $statut;
            if (in_array($candidate, [0, 1, 2], true)) {
                $statutInt = $candidate;
            }
        }

        $validSorts = ['id_desc', 'id_asc', 'date_asc', 'date_desc', 'nom_asc', 'nom_desc'];
        if (!in_array($sort, $validSorts, true)) {
            $sort = 'id_desc';
        }

        $reservations = $reservationRepository->searchFilterAndSort($search, $statutInt, $sort);

        $allReservations = $reservationRepository->findAll();
        $stats = [
            'total' => count($allReservations),
            'en_attente' => 0,
            'acceptees' => 0,
            'refusees' => 0,
        ];

        foreach ($allReservations as $reservation) {
            $status = $reservation->getStatutReservation();
            if ($status === 0) {
                $stats['en_attente']++;
            } elseif ($status === 1) {
                $stats['acceptees']++;
            } else {
                $stats['refusees']++;
            }
        }

        return $this->render('admin/reservation/list.html.twig', [
            'reservations' => $reservations,
            'stats' => $stats,
            'search' => $search,
            'statut' => $statutInt,
            'sort' => $sort,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\\d+'])]
    public function edit(
        int $id,
        Request $request,
        ReservationRepository $reservationRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $reservation = $reservationRepository->find($id);
        if ($reservation === null) {
            throw $this->createNotFoundException('Reservation introuvable.');
        }

        $form = $this->createFormBuilder($reservation)
            ->add('statutReservation', ChoiceType::class, [
                'choices' => [
                    'En attente' => 0,
                    'Acceptee' => 1,
                    'Refusee' => 2,
                ],
                'label' => 'Statut',
                'attr' => ['class' => 'w-full px-4 py-3 rounded-xl border'],
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Statut de la reservation mis a jour.');

            return $this->redirectToRoute('app_admin_reservations_list');
        }

        return $this->render('admin/reservation/edit.html.twig', [
            'reservation' => $reservation,
            'form' => $form,
        ]);
    }
}
