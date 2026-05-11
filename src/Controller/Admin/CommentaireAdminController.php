<?php

namespace App\Controller\Admin;
use App\Repository\PublicationRepository;

use App\Repository\CommentaireRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/commentaires', name: 'app_admin_commentaires_')]
class CommentaireAdminController extends AbstractController
{
    #[Route('/', name: 'list', methods: ['GET'])]
    public function list(CommentaireRepository $commentRepo): Response
    {
        $commentaires = $commentRepo->findBy([], ['date_creation' => 'DESC']);

        return $this->render('admin/commentaire/list.html.twig', [
            'commentaires' => $commentaires,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(int $id, Request $request, CommentaireRepository $commentRepo, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('admin_delete_comment_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide');
            return $this->redirectToRoute('app_admin_publications_list');
        }

        $comment = $commentRepo->find($id);
        if (!$comment) {
            $this->addFlash('error', 'Commentaire introuvable');
            return $this->redirectToRoute('app_admin_publications_list');
        }

        $em->remove($comment);
        $em->flush();

        $this->addFlash('success', 'Commentaire supprimé');
        return $this->redirectToRoute('app_admin_publications_list');
    }
}
