<?php

namespace App\Controller\Admin;

use App\Entity\LearningPath;
use App\Form\LearningPathType;
use App\Repository\LearningPathRepository;
use App\Repository\SkillRepository;
use App\Service\SkillAITutorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/skills/parcours', name: 'app_admin_learning_path_')]
class LearningPathAdminController extends AbstractController
{
    public function __construct(
        private LearningPathRepository $learningPathRepository,
        private EntityManagerInterface $entityManager,
        private SkillAITutorService $aiService,
        private SkillRepository $skillRepository,
    ) {
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->redirectToRoute('app_admin_skills_list');
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $learningPath = new LearningPath();
        $form = $this->createForm(LearningPathType::class, $learningPath);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($learningPath);
            $this->entityManager->flush();
            $this->addFlash('success', 'Parcours créé avec succès.');
            return $this->redirectToRoute('app_admin_skills_list');
        }

        return $this->render('admin/learning_path/form.html.twig', [
            'form' => $form,
            'learning_path' => $learningPath,
            'mode' => 'create',
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, int $id): Response
    {
        $learningPath = $this->learningPathRepository->find($id);
        if (!$learningPath) {
            $this->addFlash('error', 'Parcours introuvable.');
            return $this->redirectToRoute('app_admin_skills_list');
        }

        $form = $this->createForm(LearningPathType::class, $learningPath);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'Parcours modifié avec succès.');
            return $this->redirectToRoute('app_admin_skills_list');
        }

        return $this->render('admin/learning_path/form.html.twig', [
            'form' => $form,
            'learning_path' => $learningPath,
            'mode' => 'edit',
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, int $id): Response
    {
        $learningPath = $this->learningPathRepository->find($id);
        if (!$learningPath) {
            $this->addFlash('error', 'Parcours introuvable.');
            return $this->redirectToRoute('app_admin_skills_list');
        }

        $token = (string) $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_learning_path_' . $id, $token)) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_admin_skills_list');
        }

        $this->entityManager->remove($learningPath);
        $this->entityManager->flush();
        $this->addFlash('success', 'Parcours supprimé.');

        return $this->redirectToRoute('app_admin_skills_list');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // IA GENERATOR
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Step 1 – Show generation form
     */
    #[Route('/generate', name: 'generate', methods: ['GET'])]
    public function generateForm(): Response
    {
        $skills = $this->skillRepository->findBy([], ['nom_skill' => 'ASC']);

        return $this->render('admin/learning_path/generate.html.twig', [
            'skills' => $skills,
            'ai_configured' => $this->aiService->isConfigured(),
            'generated_steps' => [],
            'selected_skill_id' => null,
            'step' => 'form',
        ]);
    }

    /**
     * Step 2 – Call AI and show preview
     */
    #[Route('/generate/preview', name: 'generate_preview', methods: ['POST'])]
    public function generatePreview(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('ai_generate_lp', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('app_admin_learning_path_generate');
        }

        $skills = $this->skillRepository->findBy([], ['nom_skill' => 'ASC']);

        $skillId  = (int) $request->request->get('skill_id');
        $prompt   = trim((string) $request->request->get('prompt', ''));
        $niveau   = max(1, min(3, (int) $request->request->get('niveau', 1)));
        $nbEtapes = max(3, min(10, (int) $request->request->get('nb_etapes', 5)));

        $skill = $this->skillRepository->find($skillId);
        if (!$skill) {
            $this->addFlash('error', 'Compétence introuvable.');
            return $this->redirectToRoute('app_admin_learning_path_generate');
        }

        if (!$this->aiService->isConfigured()) {
            $this->addFlash('error', 'Aucune clé API IA configurée (GEMINI_API_KEY / XAI_API_KEY / OPENROUTER_API_KEY).');
            return $this->redirectToRoute('app_admin_learning_path_generate');
        }

        if (empty($prompt)) {
            $prompt = sprintf(
                'Génère un parcours d\'apprentissage complet pour apprendre %s.',
                $skill->getNomSkill()
            );
        }

        $generatedSteps = $this->aiService->generateLearningPaths($skill, $prompt, $niveau, $nbEtapes);

        if (empty($generatedSteps)) {
            $this->addFlash('error', 'L\'IA n\'a pas pu générer des étapes. Vérifiez vos clés API ou réessayez.');
            return $this->render('admin/learning_path/generate.html.twig', [
                'skills' => $skills,
                'ai_configured' => $this->aiService->isConfigured(),
                'generated_steps' => [],
                'selected_skill_id' => $skillId,
                'selected_skill' => $skill,
                'niveau' => $niveau,
                'nb_etapes' => $nbEtapes,
                'prompt' => $prompt,
                'step' => 'form',
            ]);
        }

        return $this->render('admin/learning_path/generate.html.twig', [
            'skills' => $skills,
            'ai_configured' => true,
            'generated_steps' => $generatedSteps,
            'selected_skill_id' => $skillId,
            'selected_skill' => $skill,
            'niveau' => $niveau,
            'nb_etapes' => $nbEtapes,
            'prompt' => $prompt,
            'step' => 'preview',
        ]);
    }

    /**
     * Step 3 – Save the (optionally edited) steps to DB
     */
    #[Route('/generate/save', name: 'generate_save', methods: ['POST'])]
    public function generateSave(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('ai_save_lp', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('app_admin_learning_path_generate');
        }

        $skillId = (int) $request->request->get('skill_id');
        $skill   = $this->skillRepository->find($skillId);

        if (!$skill) {
            $this->addFlash('error', 'Compétence introuvable.');
            return $this->redirectToRoute('app_admin_learning_path_generate');
        }

        // Retrieve submitted steps from form arrays
        $titres       = $request->request->all('titres');
        $descriptions = $request->request->all('descriptions');
        $durees       = $request->request->all('durees');
        $types        = $request->request->all('types');
        $urls         = $request->request->all('urls');
        $niveaux      = $request->request->all('niveaux');

        if (empty($titres)) {
            $this->addFlash('error', 'Aucune étape à sauvegarder.');
            return $this->redirectToRoute('app_admin_learning_path_generate');
        }

        $validTypes = ['post', 'video', 'exercice', 'quiz'];
        $saved = 0;

        foreach ($titres as $i => $titre) {
            $titre = trim((string) $titre);
            if (empty($titre)) {
                continue;
            }

            $lp = new LearningPath();
            $lp->setTitrePath(mb_substr($titre, 0, 255));
            $lp->setDescriptionSkill(mb_substr(trim((string) ($descriptions[$i] ?? '')), 0, 255));
            $lp->setDureeEstimee(max(1, min(40, (int) ($durees[$i] ?? 2))));

            $typeEtape = in_array($types[$i] ?? '', $validTypes, true) ? $types[$i] : 'post';
            $lp->setTypeEtape($typeEtape);

            $url = filter_var($urls[$i] ?? '', FILTER_VALIDATE_URL) ? $urls[$i] : '';
            $lp->setUrl($url);

            $niveau = max(1, min(3, (int) ($niveaux[$i] ?? 1)));
            $lp->setNiveauPath($niveau);

            $lp->setContextePath(0);
            $lp->setStatutPath(1);
            $lp->setSkill($skill);

            $this->entityManager->persist($lp);
            $saved++;
        }

        $this->entityManager->flush();

        $this->addFlash('success', sprintf('%d parcours générés par l\'IA ont été sauvegardés avec succès !', $saved));
        return $this->redirectToRoute('app_admin_skills_list', ['tab' => 'paths']);
    }
}
