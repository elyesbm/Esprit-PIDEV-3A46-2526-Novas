<?php

namespace App\Controller\Front;

use App\Entity\Commentaire;
use App\Entity\User;
use App\Form\CommentaireType;
use App\Repository\CommentaireRepository;
use App\Repository\PublicationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/commentaires')]
class CommentaireController extends AbstractController
{
    #[Route('/publication/{id}/ajouter', name: 'app_commentaire_ajouter', methods: ['POST'])]
    public function ajouter(
        Request $request,
        int $id,
        PublicationRepository $pubRepo,
        CommentaireRepository $commentRepo,
        UserRepository $userRepo,
        EntityManagerInterface $em,
        #[Target('comment_add.limiter')] RateLimiterFactory $commentAddLimiter
    ): Response {
        $pub = $pubRepo->find($id);
        if (!$pub) {
            $this->addFlash('error', 'Publication introuvable.');
            return $this->redirectToRoute('app_publications_index');
        }

        // mode démo : connecté OU premier user en base
        $user = $this->resolveCurrentOrFirstUser($userRepo);
        if (!$user) {
            $this->addFlash('error', 'Aucun utilisateur en base.');
            return $this->redirectToRoute('app_publications_index');
        }

        // Rate limiting : 3 commentaires / minute par IP (anti-spam)
        $limiterId = $request->getClientIp() ?? 'unknown';
        $limiter = $commentAddLimiter->create($limiterId);
        if (!$limiter->consume(1)->isAccepted()) {
            $this->addFlash('comment_error_' . $id, 'Trop de commentaires. Merci d’attendre une minute avant d’en ajouter un autre.');
            $params = [
                'open' => $id,
                'q' => (string) $request->request->get('return_q', ''),
                'sort' => (string) $request->request->get('return_sort', 'created_desc'),
                'page' => max(1, (int) $request->request->get('return_page', 1)),
            ];
            if ($request->request->get('return_contexte') !== null && $request->request->get('return_contexte') !== '') {
                $params['contexte'] = (int) $request->request->get('return_contexte');
            }
            return $this->redirectToRoute('app_publications_index', $params + ['_fragment' => 'pub-' . $id]);
        }

        // ✅ récupérer les params pour revenir au même état (recherche/tri/filtre)
        $returnQ = (string) $request->request->get('return_q', '');
        $returnSort = (string) $request->request->get('return_sort', 'created_desc');
        $returnContexte = $request->request->get('return_contexte'); // peut être null
        $returnPage = max(1, (int) $request->request->get('return_page', 1));

        $comment = new Commentaire();
        $form = $this->createForm(CommentaireType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setDateCreation(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
            $comment->setAuteur($user);
            $comment->setPublication($pub);
            $comment->setImage($user->getIMAGE() ?? '');

            $audioFile = $form->get('audio')->getData();
            if ($audioFile) {
                $uploadDir = $this->projectDir() . '/public/uploads/comment_audio';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $ext = $audioFile->guessExtension() ?: 'webm';
                $filename = sprintf('%s_%s.%s', uniqid('voice_', true), (new \DateTimeImmutable())->format('YmdHis'), $ext);
                $audioFile->move($uploadDir, $filename);
                $comment->setAudioFilename($filename);
                if (trim((string) $comment->getContenu()) === '') {
                    $comment->setContenu('[Message vocal]');
                }
            }

        $parentId = $form->get('parent_id')->getData();
        if ($parentId && is_numeric($parentId)) {
            $parent = $commentRepo->find((int) $parentId);
            if ($parent && $parent->getPublication() && $parent->getPublication()->getId() === $pub->getId()) {
                $comment->setParent($parent);
            }
        }

            $em->persist($comment);
            $em->flush();

            $this->addFlash('comment_success_' . $id, 'Commentaire ajouté.');
        } else {
        // ✅ message d’erreur détaillé (1er message)
        $firstError = 'Commentaire invalide.';
        foreach ($form->getErrors(true) as $error) {
            if (method_exists($error, 'getMessage')) {
                /** @var string $message */
                $message = $error->getMessage();
                $firstError = $message;
                break;
            }
        }

        // ✅ flash ciblé UNIQUEMENT pour cette publication
            $this->addFlash('comment_error_' . $id, $firstError);
        }

    // ✅ retour sur la même publication + ouvrir commentaires
    $params = [
        'open' => $id,
        'q' => $returnQ,
        'sort' => $returnSort,
        'page' => $returnPage,
    ];
    if ($returnContexte !== null && $returnContexte !== '') {
        $params['contexte'] = (int) $returnContexte;
    }

        return $this->redirectToRoute('app_publications_index', $params + [
            '_fragment' => 'pub-' . $id,
        ]);
    }
    #[Route('/{id}/supprimer', name: 'app_commentaire_supprimer', methods: ['POST'])]
    public function supprimer(
        Request $request,
        int $id,
        CommentaireRepository $commentRepo,
        UserRepository $userRepo,
        EntityManagerInterface $em
    ): Response {
        if (!$this->isCsrfTokenValid('delete_comment_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
            return $this->redirectToRoute('app_publications_index');
        }

        $comment = $commentRepo->find($id);
        if (!$comment) {
            $this->addFlash('error', 'Commentaire introuvable.');
            return $this->redirectToRoute('app_publications_index');
        }

        // mode démo : connecté OU premier user
        $user = $this->resolveCurrentOrFirstUser($userRepo);
        if (!$user) {
            $this->addFlash('error', 'Aucun utilisateur en base.');
            return $this->redirectToRoute('app_publications_index');
        }

        $em->remove($comment);
        $em->flush();

        $this->addFlash('success', 'Commentaire supprimé.');
        return $this->redirectToRoute('app_publications_index');
    }

    #[Route('/{id}/modifier', name: 'app_commentaire_modifier', methods: ['GET', 'POST'])]
    public function modifier(
        Request $request,
        int $id,
        CommentaireRepository $commentRepo,
        EntityManagerInterface $em
    ): Response {
        $comment = $commentRepo->find($id);
        if (!$comment) {
            $this->addFlash('error', 'Commentaire introuvable.');
            return $this->redirectToRoute('app_publications_index');
        }

        $form = $this->createForm(CommentaireType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Commentaire modifié.');
            return $this->redirectToRoute('app_publications_index');
        }

        return $this->render('front/publication/modifier2.html.twig', [
            'commentaire' => $comment,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/nouveau/publication/{id}', name: 'app_commentaire_nouveau', methods: ['GET', 'POST'])]
    public function nouveau(
        Request $request,
        int $id,
        PublicationRepository $pubRepo,
        UserRepository $userRepo,
        EntityManagerInterface $em,
        #[Target('comment_add.limiter')] RateLimiterFactory $commentAddLimiter
    ): Response {
        $pub = $pubRepo->find($id);
        if (!$pub) {
            $this->addFlash('error', 'Publication introuvable.');
            return $this->redirectToRoute('app_publications_index');
        }

        // mode démo : connecté OU premier user
        $user = $this->resolveCurrentOrFirstUser($userRepo);
        if (!$user) {
            $this->addFlash('error', 'Aucun utilisateur en base.');
            return $this->redirectToRoute('app_publications_index');
        }

        if ($request->isMethod('POST')) {
            $limiterId = $request->getClientIp() ?? 'unknown';
            $limiter = $commentAddLimiter->create($limiterId);
            if (!$limiter->consume(1)->isAccepted()) {
                $this->addFlash('error', 'Trop de commentaires. Merci d’attendre une minute avant d’en ajouter un autre.');
                return $this->render('front/publication/nouveau2.html.twig', [
                    'form' => $this->createForm(CommentaireType::class, new Commentaire())->createView(),
                    'publication' => $pub,
                ]);
            }
        }

        $comment = new Commentaire();
        $form = $this->createForm(CommentaireType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setDateCreation(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
            $comment->setAuteur($user);
            $comment->setPublication($pub);
            $comment->setImage($user->getIMAGE() ?? '');

            $em->persist($comment);
            $em->flush();

            $this->addFlash('success', 'Commentaire ajouté.');
            return $this->redirectToRoute('app_publications_index');
        }

        return $this->render('front/publication/nouveau2.html.twig', [
            'form' => $form->createView(),
            'publication' => $pub,
        ]);
    }

    private function resolveCurrentOrFirstUser(UserRepository $userRepo): ?User
    {
        $user = $this->getUser();
        if ($user instanceof User) {
            return $user;
        }

        $firstUser = $userRepo->findOneBy([], ['id' => 'ASC']);
        return $firstUser instanceof User ? $firstUser : null;
    }

    private function projectDir(): string
    {
        $projectDir = $this->getParameter('kernel.project_dir');
        if (!is_string($projectDir)) {
            throw new \RuntimeException('Invalid kernel.project_dir parameter.');
        }

        return $projectDir;
    }
}
