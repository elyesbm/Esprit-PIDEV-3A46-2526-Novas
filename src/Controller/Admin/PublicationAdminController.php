<?php

namespace App\Controller\Admin;

use App\Controller\Front\PublicationController as FrontPublicationController;
use App\Repository\CommentaireRepository;
use App\Repository\PublicationReactionRepository;
use App\Repository\PublicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/publications', name: 'app_admin_publications_')]
class PublicationAdminController extends AbstractController
{
    #[Route('/', name: 'list', methods: ['GET'])]
    public function list(
        Request $request,
        PublicationRepository $publicationRepo,
        CommentaireRepository $commentRepo,
        PublicationReactionRepository $reactionRepo
    ): Response {
        // ✅ recherche + tri
        $q = trim((string) $request->query->get('q', ''));
        $sort = (string) $request->query->get('sort', 'created_desc'); // created_desc | updated_desc

        $orderBy = match ($sort) {
            'updated_desc' => ['date_modification' => 'DESC'],
            default        => ['date_creation' => 'DESC'],
        };

        // ✅ publications triées (DB)
        $publications = $publicationRepo->findBy([], $orderBy);

        // ✅ filtre recherche (titre + contenu + auteur nom/prénom)
        if ($q !== '') {
            $publications = array_values(array_filter($publications, function ($pub) use ($q) {
                $titre = (string) ($pub->getTitre() ?? '');
                $contenu = (string) ($pub->getContenu() ?? '');

                $auteurNom = '';
                $auteurPrenom = '';
                if ($pub->getAuteur()) {
                    $auteurNom = (string) ($pub->getAuteur()->getNOM() ?? '');
                    $auteurPrenom = (string) ($pub->getAuteur()->getPRENOM() ?? '');
                }

                return stripos($titre, $q) !== false
                    || stripos($contenu, $q) !== false
                    || stripos($auteurNom, $q) !== false
                    || stripos($auteurPrenom, $q) !== false;
            }));
        }

        // ✅ stats
        $totalCommentaires = $commentRepo->count([]);
        $totalPublications = $publicationRepo->count([]);

        $totalLikes = 0;
        foreach ($publications as $publication) {
            $totalLikes += $publication->getLikes() ?? 0;
        }

        // Signalements : basés sur publication_reaction (value = 2)
        $publicationIds = array_values(array_filter(
            array_map(static fn($pub) => $pub->getId(), $publications),
            static fn($id) => $id !== null
        ));
        $totalSignalements = $reactionRepo->countSignals();
        $signalCountsByPublication = [];
        foreach ($publications as $publication) {
            $pubId = $publication->getId();
            if ($pubId !== null) {
                $signalCountsByPublication[$pubId] = (int) $reactionRepo->count([
                    'publication' => $publication,
                    'value' => 2,
                ]);
            }
        }
        $publicationsSignalees = array_values(array_filter($publications, static fn($pub) => ($signalCountsByPublication[$pub->getId()] ?? 0) > 0));
        $likersByPublication = $reactionRepo->findLikersByPublicationIds($publicationIds);
        $signalersByPublication = $reactionRepo->findSignalersByPublicationIds($publicationIds);

        // ✅ commentaires (inchangé, si tu l’utilises encore dans le twig)
        $commentaires = $commentRepo->findBy([], ['date_creation' => 'DESC']);

        return $this->render('admin/publication/list.html.twig', [
            'publications' => $publications,
            'commentaires' => $commentaires,
            'likersByPublication' => $likersByPublication,
            'signalersByPublication' => $signalersByPublication,
            'signalCountsByPublication' => $signalCountsByPublication,
            'totalPublications' => $totalPublications,
            'totalCommentaires' => $totalCommentaires,
            'totalLikes' => $totalLikes,
            'totalSignalements' => $totalSignalements,
            'publicationsSignalees' => $publicationsSignalees,
            'report_causes' => FrontPublicationController::REPORT_CAUSES,

            // ✅ pour twig (recherche + tri)
            'q' => $q,
            'sort' => $sort,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET'])]
    public function edit(
        int $id,
        PublicationRepository $publicationRepo,
        PublicationReactionRepository $reactionRepo
    ): Response
    {
        $publication = $publicationRepo->find($id);
        if (!$publication) {
            throw $this->createNotFoundException('Publication introuvable');
        }

        $likers = $reactionRepo->findLikersByPublicationId($id);
        $signalers = $reactionRepo->findSignalersByPublicationId($id);

        return $this->render('admin/publication/form.html.twig', [
            'publication' => $publication,
            'likers' => $likers,
            'signalers' => $signalers,
            'report_causes' => FrontPublicationController::REPORT_CAUSES,
        ]);
    }

    #[Route('/{id}/toggle', name: 'toggle', methods: ['POST'])]
    public function toggleStatut(int $id, PublicationRepository $publicationRepo, EntityManagerInterface $em): Response
    {
        $publication = $publicationRepo->find($id);
        if (!$publication) {
            $this->addFlash('error', 'Publication introuvable');
            return $this->redirectToRoute('app_admin_publications_list');
        }

        $publication->setStatut($publication->getStatut() == 1 ? 0 : 1);
        $em->flush();

        $this->addFlash('success', 'Statut mis à jour');
        return $this->redirectToRoute('app_admin_publications_list');
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(int $id, Request $request, PublicationRepository $publicationRepo, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('admin_delete_pub_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide');
            return $this->redirectToRoute('app_admin_publications_list');
        }

        $publication = $publicationRepo->find($id);
        if (!$publication) {
            $this->addFlash('error', 'Publication introuvable');
            return $this->redirectToRoute('app_admin_publications_list');
        }

        $em->remove($publication);
        $em->flush();

        $this->addFlash('success', 'Publication supprimée');
        return $this->redirectToRoute('app_admin_publications_list');
    }

    #[Route('/{id}/approve', name: 'approve', methods: ['POST'])]
    public function approve(int $id, Request $request, PublicationRepository $publicationRepo, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('admin_pub_action_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide');
            return $this->redirectToRoute('app_admin_publications_edit', ['id' => $id]);
        }

        $publication = $publicationRepo->find($id);
        if (!$publication) {
            throw $this->createNotFoundException('Publication introuvable');
        }

        $publication->setStatut(1);
        $em->flush();

        $this->addFlash('success', 'Publication approuvée');
        return $this->redirectToRoute('app_admin_publications_edit', ['id' => $id]);
    }

    #[Route('/{id}/hide', name: 'hide', methods: ['POST'])]
    public function hide(int $id, Request $request, PublicationRepository $publicationRepo, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('admin_pub_action_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide');
            return $this->redirectToRoute('app_admin_publications_edit', ['id' => $id]);
        }

        $publication = $publicationRepo->find($id);
        if (!$publication) {
            throw $this->createNotFoundException('Publication introuvable');
        }

        $publication->setStatut(0);
        $em->flush();

        $this->addFlash('success', 'Publication masquée');
        return $this->redirectToRoute('app_admin_publications_edit', ['id' => $id]);
    }
}
