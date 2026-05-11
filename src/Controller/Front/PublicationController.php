<?php

namespace App\Controller\Front;

use App\Entity\Commentaire;
use App\Entity\Publication;
use App\Entity\PublicationReaction;
use App\Entity\User;
use App\Form\CommentaireType;
use App\Form\PublicationType;
use App\Repository\CommentaireRepository;
use App\Service\ToxicityAnalysisService;
use App\Repository\PublicationRepository;
use App\Repository\PublicationReactionRepository;
use App\Repository\UserRepository;
use App\Service\HuggingFaceImageService;
use App\Service\PublicationTranslationService;
use App\Service\SentimentAnalysisService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/publications')]
class PublicationController extends AbstractController
{
    private const TRANSLATION_LANGUAGES = [
        'en' => 'English',
        'es' => 'Espanol',
        'de' => 'Deutsch',
        'it' => 'Italiano',
        'ar' => 'Arabic',
    ];

    /** Causes de signalement (code => libellé) */
    public const REPORT_CAUSES = [
        'spam' => 'Spam ou publicité',
        'inappropriate' => 'Contenu inapproprié',
        'harassment' => 'Harcèlement ou intimidation',
        'false_info' => 'Fausse information',
        'violence' => 'Violence ou menaces',
        'hate' => 'Discours haineux',
        'other' => 'Autre',
    ];

    /**
     * Extrait l'ID d'une vidéo YouTube depuis une URL ou retourne la chaîne si c'est déjà un ID (11 caractères).
     */
    private static function normalizeYoutubeVideoId(?string $input): ?string
    {
        if ($input === null || trim($input) === '') {
            return null;
        }
        $input = trim($input);
        if (preg_match('#(?:youtube\.com/watch\?v=|youtu\.be/|youtube\.com/embed/)([A-Za-z0-9_-]{10,12})#', $input, $m)) {
            return $m[1];
        }
        if (preg_match('#^[A-Za-z0-9_-]{10,12}$#', $input)) {
            return $input;
        }
        return null;
    }

    private static function safeJsonMessage(string $message): string
    {
        $clean = @iconv('UTF-8', 'UTF-8//IGNORE', $message);
        if ($clean === false || $clean === '') {
            return 'Erreur interne.';
        }

        return $clean;
    }

    /**
     * ✅ User courant OU fallback (1er user en base)
     */
    private function getCurrentUserOrFallback(UserRepository $userRepo): ?User
    {
        $user = $this->getUser();
        if ($user instanceof User) {
            return $user;
        }

        return $userRepo->findOneBy([], ['id' => 'ASC']);
    }

    #[Route('/', name: 'app_publications_index', methods: ['GET'])]
    public function index(
        Request $request,
        PublicationRepository $repo,
        CommentaireRepository $commentRepo,
        UserRepository $userRepo,
        PublicationReactionRepository $reactionRepo,
        PaginatorInterface $paginator,
        SentimentAnalysisService $sentimentService
    ): Response
    {
        $contexteFilter = $request->query->get('contexte');
        $q = trim((string) $request->query->get('q', ''));
        $sort = (string) $request->query->get('sort', 'created_desc');

        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = 5;

        $qb = $repo->createQueryBuilder('p')
            ->leftJoin('p.auteur', 'a')
            ->addSelect('a')
            ->andWhere('p.statut = :statut')
            ->setParameter('statut', 1);

        if ($contexteFilter !== null && in_array((int) $contexteFilter, [1, 2], true)) {
            $qb->andWhere('p.contexte = :contexte')
                ->setParameter('contexte', (int) $contexteFilter);
        }

        if ($q !== '') {
            $qb->andWhere(
                'LOWER(p.titre) LIKE :q
                 OR LOWER(p.contenu) LIKE :q
                 OR LOWER(a.NOM) LIKE :q
                 OR LOWER(a.PRENOM) LIKE :q'
            )->setParameter('q', '%' . strtolower($q) . '%');
        }

        match ($sort) {
            'updated_desc' => $qb->orderBy('p.date_modification', 'DESC')
                ->addOrderBy('p.date_creation', 'DESC'),
            default => $qb->orderBy('p.date_creation', 'DESC'),
        };

        $publicationsPage = $paginator->paginate($qb, $page, $perPage);

        $totalPublications = (int) $publicationsPage->getTotalItemCount();
        $totalPages = max(1, (int) ceil($totalPublications / $perPage));
        $page = (int) $publicationsPage->getCurrentPageNumber();

        $currentUser = $this->getCurrentUserOrFallback($userRepo);
        $authenticatedUser = $this->getUser();

        $userReactions = [];
        $signalCounts = [];
        $likeCounts = [];
        $publicationIds = [];
        $sentimentScores = [];
        foreach ($publicationsPage as $pub) {
            $pubId = $pub->getId();
            if ($pubId === null) {
                continue;
            }
            $publicationIds[] = $pubId;

            $likeCounts[$pubId] = (int) $reactionRepo->count([
                'publication' => $pub,
                'value' => 1,
            ]);
            $signalCounts[$pubId] = (int) $reactionRepo->count([
                'publication' => $pub,
                'value' => 2,
            ]);

            $userReactions[$pubId] = 0;
            if ($authenticatedUser instanceof User) {
                $existingReaction = $reactionRepo->findOneBy([
                    'publication' => $pub,
                    'user' => $authenticatedUser,
                ]);
                if ($existingReaction) {
                    $userReactions[$pubId] = $existingReaction->getValue();
                }
            }

            // Analyse de sentiments (1–5 étoiles) sur le contenu de la publication.
            $content = $pub->getContenu();
            if (\is_string($content)) {
                // On retire le HTML éventuel, le modèle travaille sur du texte brut.
                $plainText = trim(strip_tags($content));
                $stars = $sentimentService->analyze($plainText);
                if ($stars !== null) {
                    $sentimentScores[$pubId] = $stars;
                }
            }
        }
        $likersByPublication = $reactionRepo->findLikersByPublicationIds($publicationIds);

        $commentForms = [];
        $commentRoots = [];
        foreach ($publicationsPage as $pub) {
            $commentRoots[$pub->getId()] = $commentRepo->findRootsByPublication($pub);
            $commentForms[$pub->getId()] = $this->createForm(
                CommentaireType::class,
                new Commentaire(),
                [
                    'action' => $this->generateUrl('app_commentaire_ajouter', ['id' => $pub->getId()]) . '#pub-' . $pub->getId(),
                    'method' => 'POST',
                ]
            )->createView();
        }

        return $this->render('front/publication/index.html.twig', [
            'publications' => $publicationsPage,
            'comment_forms' => $commentForms,
            'comment_roots' => $commentRoots,
            'current_user_id' => $currentUser ? $currentUser->getId() : null,
            'contexte_filter' => $contexteFilter !== null ? (int) $contexteFilter : null,
            'q' => $q,
            'sort' => $sort,
            'page' => $page,
            'per_page' => $perPage,
            'total_publications' => $totalPublications,
            'total_pages' => $totalPages,
            'user_reactions' => $userReactions,
            'signal_counts' => $signalCounts,
            'like_counts' => $likeCounts,
            'likers_by_publication' => $likersByPublication,
            'auth_user' => $authenticatedUser instanceof User ? [
                'id' => $authenticatedUser->getId(),
                'prenom' => $authenticatedUser->getPRENOM(),
                'nom' => $authenticatedUser->getNOM(),
                'image' => $authenticatedUser->getIMAGE(),
            ] : null,
            'translation_languages' => self::TRANSLATION_LANGUAGES,
            'sentiment_scores' => $sentimentScores,
            'report_causes' => self::REPORT_CAUSES,
        ]);
    }


    #[Route('/generer-image', name: 'app_publication_generer_image', methods: ['POST'])]
    public function genererImage(
        Request $request,
        HuggingFaceImageService $hfImageService
    ): Response {
        try {
            $data = json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $this->json(['ok' => false, 'message' => 'Corps JSON invalide.'], 400);
        }
        $prompt = trim((string) ($data['prompt'] ?? ''));
        if ($prompt === '') {
            return $this->json(['ok' => false, 'message' => 'Prompt requis.'], 400);
        }

        try {
            $images = $hfImageService->generateImages($prompt, 1);
        } catch (\Throwable $e) {
            return $this->json(['ok' => false, 'message' => self::safeJsonMessage($e->getMessage())], 500);
        }

        if (empty($images) || !isset($images[0]['base64']) || !\is_string($images[0]['base64'])) {
            return $this->json(['ok' => false, 'message' => 'Aucune image générée.'], 500);
        }

        $binary = base64_decode(trim($images[0]['base64']), true);
        if ($binary === false) {
            return $this->json(['ok' => false, 'message' => 'Image IA invalide (base64).'], 500);
        }

        try {
            $uploadDir = $this->projectDir() . '/public/uploads/publication_images';
            if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
                throw new \RuntimeException('Impossible de creer le dossier des images.');
            }

            $filename = 'hf_' . uniqid('', true) . '_' . time() . '.png';
            $path = $uploadDir . '/' . $filename;
            $written = file_put_contents($path, $binary);
            if ($written === false) {
                throw new \RuntimeException('Impossible d\'enregistrer l\'image.');
            }
        } catch (\Throwable $e) {
            return $this->json(['ok' => false, 'message' => self::safeJsonMessage($e->getMessage())], 500);
        }

        return $this->json(['ok' => true, 'filename' => $filename]);
    }

    #[Route('/nouvelle', name: 'app_publication_nouvelle', methods: ['GET', 'POST'])]
    public function nouvelle(
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepo,
        ToxicityAnalysisService $toxicityService,
        LoggerInterface $logger
    ): Response {
        $user = $this->getCurrentUserOrFallback($userRepo);

        if (!$user) {
            $this->addFlash('error', 'Aucun utilisateur en base.');
            return $this->redirectToRoute('app_publications_index');
        }

        $pub = new Publication();
        $pub->setContexte(1);
        $form = $this->createForm(PublicationType::class, $pub);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $textToCheck = ($pub->getTitre() ?? '') . ' ' . ($pub->getContenu() ?? '');
            try {
                if ($toxicityService->isToxic($textToCheck)) {
                    $this->addFlash('error', 'Votre message est haineux. Merci de modifier votre texte avant de publier.');
                    return $this->render('front/publication/nouvelle.html.twig', [
                        'form' => $form->createView(),
                    ]);
                }
            } catch (\RuntimeException $e) {
                $logger->warning('Toxicity check unavailable on publication create.', [
                    'message' => $e->getMessage(),
                ]);
                $this->addFlash('error', $e->getMessage());
                return $this->render('front/publication/nouvelle.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            $anonyme = (bool) $form->get('anonyme')->getData();

            $pub->setYoutubeVideoId(self::normalizeYoutubeVideoId($pub->getYoutubeVideoId()));
            $pub->setStatut(1);
            $pub->setDateCreation(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
            $pub->setAuteur($user);
            // Use an explicit marker for anonymous posts to avoid confusion
            // when the user has no profile image.
            $pub->setImageAuteur($anonyme ? 'ANONYMOUS' : ($user->getIMAGE() ?: 'VISIBLE'));

            $em->persist($pub);
            $em->flush();

            $this->addFlash('success', 'Publication créée.');
            return $this->redirectToRoute('app_publications_index');
        }

        return $this->render('front/publication/nouvelle.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_publication_modifier', methods: ['GET', 'POST'])]
    public function modifier(
        Request $request,
        int $id,
        PublicationRepository $repo,
        UserRepository $userRepo,
        EntityManagerInterface $em,
        ToxicityAnalysisService $toxicityService,
        LoggerInterface $logger
    ): Response {
        $pub = $repo->find($id);
        if (!$pub) {
            $this->addFlash('error', 'Publication introuvable.');
            return $this->redirectToRoute('app_publications_index');
        }

        $user = $this->getCurrentUserOrFallback($userRepo);
        if (!$user || !$pub->getAuteur() || $pub->getAuteur()->getId() !== $user->getId()) {
            $this->addFlash('error', 'Action non autorisée.');
            return $this->redirectToRoute('app_publications_index');
        }

        $form = $this->createForm(PublicationType::class, $pub, [
            'with_anonyme' => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $textToCheck = ($pub->getTitre() ?? '') . ' ' . ($pub->getContenu() ?? '');
            try {
                if ($toxicityService->isToxic($textToCheck)) {
                    $this->addFlash('error', 'Votre message est haineux. Merci de modifier votre texte avant de publier.');
                    return $this->render('front/publication/modifier.html.twig', [
                        'publication' => $pub,
                        'form' => $form->createView(),
                    ]);
                }
            } catch (\RuntimeException $e) {
                $logger->warning('Toxicity check unavailable on publication update.', [
                    'publication_id' => $pub->getId(),
                    'message' => $e->getMessage(),
                ]);
                $this->addFlash('error', $e->getMessage());
                return $this->render('front/publication/modifier.html.twig', [
                    'publication' => $pub,
                    'form' => $form->createView(),
                ]);
            }

            $pub->setYoutubeVideoId(self::normalizeYoutubeVideoId($pub->getYoutubeVideoId()));
            $pub->setDateModification(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
            $em->flush();
            $this->addFlash('success', 'Publication modifiée.');
            return $this->redirectToRoute('app_publications_index');
        }

        return $this->render('front/publication/modifier.html.twig', [
            'publication' => $pub,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/supprimer', name: 'app_publication_supprimer', methods: ['POST'])]
    public function supprimerPublication(
        Request $request,
        int $id,
        PublicationRepository $repo,
        UserRepository $userRepo,
        EntityManagerInterface $em
    ): Response {
        $pub = $repo->find($id);
        if (!$pub) {
            $this->addFlash('error', 'Publication introuvable.');
            return $this->redirectToRoute('app_publications_index');
        }

        $user = $this->getCurrentUserOrFallback($userRepo);
        if (!$user || !$pub->getAuteur() || $pub->getAuteur()->getId() !== $user->getId()) {
            $this->addFlash('error', 'Action non autorisée.');
            return $this->redirectToRoute('app_publications_index');
        }

        if (! $this->isCsrfTokenValid('delete_publication_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('app_publications_index');
        }

        $em->remove($pub);
        $em->flush();

        $this->addFlash('success', 'Publication supprimée.');
        return $this->redirectToRoute('app_publications_index');
    }

    #[Route('/supprimees', name: 'app_publications_supprimees', methods: ['GET'])]
    public function supprimees(
        UserRepository $userRepo,
        PublicationRepository $repo
    ): Response {
        $user = $this->getCurrentUserOrFallback($userRepo);
        if (!$user) {
            $this->addFlash('error', 'Aucun utilisateur.');
            return $this->redirectToRoute('app_publications_index');
        }

        $deleted = $repo->findDeletedByAuthor($user);

        return $this->render('front/publication/supprimees.html.twig', [
            'publications' => $deleted,
        ]);
    }

    #[Route('/{id}/restaurer', name: 'app_publication_restaurer', methods: ['POST'])]
    public function restaurer(
        Request $request,
        int $id,
        PublicationRepository $repo,
        UserRepository $userRepo,
        EntityManagerInterface $em
    ): Response {
        $em->getFilters()->disable('softdeleteable');
        $pub = $repo->find($id);
        $em->getFilters()->enable('softdeleteable');
        if (!$pub || $pub->getDeletedAt() === null) {
            $this->addFlash('error', 'Publication introuvable.');
            return $this->redirectToRoute('app_publications_index');
        }

        $user = $this->getCurrentUserOrFallback($userRepo);
        if (!$user || !$pub->getAuteur() || $pub->getAuteur()->getId() !== $user->getId()) {
            $this->addFlash('error', 'Action non autorisée.');
            return $this->redirectToRoute('app_publications_supprimees');
        }

        if (! $this->isCsrfTokenValid('restore_publication_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('app_publications_supprimees');
        }

        $pub->setDeletedAt(null);
        $em->flush();

        $this->addFlash('success', 'Publication restaurée.');
        return $this->redirectToRoute('app_publications_index');
    }

    #[Route('/{id}/signal', name: 'app_publication_signal', methods: ['POST'])]
    public function signal(
        Request $request,
        int $id,
        PublicationRepository $repo,
        PublicationReactionRepository $reactionRepo,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['ok' => false, 'message' => 'Connexion requise.'], 401);
        }

        $pub = $repo->find($id);
        if (!$pub || $pub->getStatut() != 1) {
            return $this->json(['ok' => false, 'message' => 'Publication introuvable ou inactive.'], 404);
        }

        $reason = null;
        $payload = json_decode($request->getContent(), true) ?: $request->request->all();
        if (\is_array($payload) && isset($payload['reason']) && \is_string($payload['reason'])) {
            $reason = trim($payload['reason']);
            if ($reason !== '' && !\array_key_exists($reason, self::REPORT_CAUSES)) {
                return $this->json(['ok' => false, 'message' => 'Cause de signalement invalide.'], 400);
            }
            if ($reason === '') {
                $reason = null;
            }
        }

        try {
            $result = $this->applyReaction($pub, $user, 2, $reactionRepo, $em, $reason);

            // Si la publication atteint 2 signalements, elle est supprimée automatiquement (soft-delete)
            if (($result['signals'] ?? 0) >= 2) {
                $pub->setDeletedAt(new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')));
                $em->flush();
                $result['deleted'] = true;
            }

            return $this->json($result);
        } catch (\Throwable $e) {
            return $this->json([
                'ok' => false,
                'message' => 'Erreur lors du signalement: ' . $e->getMessage(),
            ], 500);
        }
    }

    #[Route('/{id}/translate', name: 'app_publication_translate', methods: ['POST'])]
    public function translate(
        int $id,
        Request $request,
        PublicationRepository $repo,
        PublicationTranslationService $translationService
    ): Response {
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            $payload = $request->request->all();
        }

        $target = strtolower(trim((string) ($payload['target'] ?? 'en')));
        if (!array_key_exists($target, self::TRANSLATION_LANGUAGES)) {
            return $this->json([
                'ok' => false,
                'message' => 'Langue non supportee.',
            ], 400);
        }

        $pub = $repo->find($id);
        if (!$pub || $pub->getStatut() !== 1) {
            return $this->json([
                'ok' => false,
                'message' => 'Publication introuvable.',
            ], 404);
        }

        try {
            $translated = $translationService->translatePublication(
                (string) ($pub->getTitre() ?? ''),
                (string) ($pub->getContenu() ?? ''),
                $target
            );
        } catch (\RuntimeException $e) {
            return $this->json([
                'ok' => false,
                'message' => 'Traduction indisponible. Verifiez la configuration de l API.',
            ], 502);
        }

        return $this->json([
            'ok' => true,
            'title' => $translated['title'],
            'content' => $translated['content'],
            'target' => $target,
            'language_label' => self::TRANSLATION_LANGUAGES[$target],
        ]);
    }

    #[Route('/{id}/like', name: 'app_publication_like', methods: ['POST'])]
    public function like(
        int $id,
        PublicationRepository $repo,
        PublicationReactionRepository $reactionRepo,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['ok' => false, 'message' => 'Connexion requise.'], 401);
        }

        $pub = $repo->find($id);
        if (!$pub || $pub->getStatut() != 1) {
            return $this->json(['ok' => false], 404);
        }

        $result = $this->applyReaction($pub, $user, 1, $reactionRepo, $em);

        return $this->json($result);
    }

    /**
     * @return array{ok: bool, likes: int, signals: int, user_reaction: int}
     */
    private function applyReaction(
        Publication $pub,
        User $user,
        int $newValue,
        PublicationReactionRepository $reactionRepo,
        EntityManagerInterface $em,
        ?string $signalReason = null
    ): array {
        $reaction = $reactionRepo->findOneBy([
            'publication' => $pub,
            'user' => $user,
        ]);

        $previousValue = $reaction?->getValue();
        $changed = false;

        if ($reaction === null) {
            $reaction = (new PublicationReaction())
                ->setPublication($pub)
                ->setUser($user)
                ->setValue($newValue);
            if ($newValue === 2) {
                $reaction->setSignalReason($signalReason);
            }
            $em->persist($reaction);
            $changed = true;
        } elseif ($previousValue === $newValue) {
            $em->remove($reaction);
            $reaction = null;
            $changed = true;
        } elseif ($previousValue !== $newValue) {
            $reaction->setValue($newValue);
            if ($newValue === 2) {
                $reaction->setSignalReason($signalReason);
            }
            $changed = true;
        }

        if ($changed) {
            $em->flush();
        }

        $likes = (int) $reactionRepo->count([
            'publication' => $pub,
            'value' => 1,
        ]);
        $signals = (int) $reactionRepo->count([
            'publication' => $pub,
            'value' => 2,
        ]);

        if ($pub->getLikes() !== $likes) {
            $pub->setLikes($likes);
            $em->flush();
        }

        return [
            'ok'    => true,
            'likes' => $likes,
            'signals' => $signals,
            'user_reaction' => $reaction?->getValue() ?? 0,
        ];
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
