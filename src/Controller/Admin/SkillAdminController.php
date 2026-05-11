<?php

namespace App\Controller\Admin;

use App\Entity\Skill;
use App\Form\SkillType;
use App\Repository\LearningPathRepository;
use App\Repository\SkillRepository;
use App\Service\SkillMarketStatsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/skills', name: 'app_admin_skills_')]
class SkillAdminController extends AbstractController
{
    public function __construct(
        private SkillRepository $skillRepository,
        private LearningPathRepository $learningPathRepository,
        private EntityManagerInterface $entityManager,
        private SkillMarketStatsService $marketStatsService,
    ) {
    }

    #[Route('/', name: 'list')]
    public function list(Request $request): Response
    {
        $qRaw = $request->query->get('q');
        $typeRaw = $request->query->get('type');
        $categorieRaw = $request->query->get('categorie');
        $qPathRaw = $request->query->get('q_path');
        $niveauPathRaw = $request->query->get('niveau_path');
        $statutPathRaw = $request->query->get('statut_path');

        $q = is_string($qRaw) && trim($qRaw) !== '' ? trim($qRaw) : null;
        $type = is_string($typeRaw) && trim($typeRaw) !== '' ? trim($typeRaw) : null;
        $categorie = is_string($categorieRaw) && trim($categorieRaw) !== '' ? trim($categorieRaw) : null;
        $qPath = is_string($qPathRaw) && trim($qPathRaw) !== '' ? trim($qPathRaw) : null;
        $niveauPath = is_string($niveauPathRaw) && trim($niveauPathRaw) !== '' ? (int) $niveauPathRaw : null;
        $statutPath = is_string($statutPathRaw) && trim($statutPathRaw) !== '' ? (int) $statutPathRaw : null;

        $skills = $this->skillRepository->searchAndFilter($q, $type, $categorie);
        $learningPaths = $this->learningPathRepository->searchAndFilter(
            $qPath,
            $niveauPath,
            $statutPath
        );
        $totalSkills = $this->skillRepository->count([]);
        $totalPaths = $this->learningPathRepository->count([]);
        $tendanceCounts = $this->skillRepository->getCountByTendance();
        $skillsForChart = $this->skillRepository->findWithMarketStats();
        $chartSkillsData = array_map(fn (Skill $s) => [
            'nom' => $s->getNomSkill(),
            'offres' => $s->getNombreOffresAssociees(),
            'tendance' => $s->getTendanceMarche(),
        ], $skillsForChart);
        $categories = ['Communication', 'Programmation', 'Management', 'Data Science', 'Bien-être', 'Développement Web', 'Design', 'Marketing'];

        return $this->render('admin/skill/list.html.twig', [
            'skills' => $skills,
            'learningPaths' => $learningPaths,
            'totalSkills' => $totalSkills,
            'totalPaths' => $totalPaths,
            'tendanceCounts' => $tendanceCounts,
            'skillsForChart' => $skillsForChart,
            'chartSkillsData' => $chartSkillsData,
            'categories' => $categories,
            'search_q' => $q,
            'search_type' => $type,
            'search_categorie' => $categorie,
            'search_q_path' => $qPath,
            'search_niveau_path' => $niveauPath,
            'search_statut_path' => $statutPath,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $skill = new Skill();
        $form = $this->createForm(SkillType::class, $skill);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($skill);
            $this->entityManager->flush();
            $this->addFlash('success', 'Compétence créée avec succès.');
            return $this->redirectToRoute('app_admin_skills_list');
        }

        return $this->render('admin/skill/form.html.twig', [
            'form' => $form,
            'skill' => $skill,
            'mode' => 'create',
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, int $id): Response
    {
        $skill = $this->skillRepository->find($id);
        if (!$skill) {
            $this->addFlash('error', 'Compétence introuvable.');
            return $this->redirectToRoute('app_admin_skills_list');
        }

        $form = $this->createForm(SkillType::class, $skill);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'Compétence modifiée avec succès.');
            return $this->redirectToRoute('app_admin_skills_list');
        }

        return $this->render('admin/skill/form.html.twig', [
            'form' => $form,
            'skill' => $skill,
            'mode' => 'edit',
        ]);
    }

    #[Route('/refresh-all-stats', name: 'refresh_all_stats', methods: ['POST'])]
    public function refreshAllStats(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('refresh_all_skills_stats', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_admin_skills_list');
        }

        $skills = $this->skillRepository->findBy([], ['nom_skill' => 'ASC']);
        $updated = 0;
        foreach ($skills as $skill) {
            if ($this->marketStatsService->fetchAndUpdateSkillStats($skill) !== null) {
                $updated++;
            }
        }
        $this->entityManager->flush();

        $this->addFlash('success', sprintf('%d compétence(s) mise(s) à jour.', $updated));
        return $this->redirectToRoute('app_admin_skills_list');
    }

    #[Route('/{id}/refresh-stats', name: 'refresh_stats', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function refreshStats(Request $request, int $id): Response
    {
        $skill = $this->skillRepository->find($id);
        if (!$skill) {
            $this->addFlash('error', 'Compétence introuvable.');
            return $this->redirectToRoute('app_admin_skills_list');
        }

        if (!$this->isCsrfTokenValid('refresh_stats_skill_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_admin_skills_list');
        }

        $result = $this->marketStatsService->fetchAndUpdateSkillStats($skill);
        if ($result !== null) {
            $this->entityManager->flush();
            $this->addFlash('success', sprintf(
                'Statistiques mises à jour : %d offres, score %d/5, tendance %s.',
                $result['nombre_offres'],
                $result['score_demande'],
                $result['tendance'],
            ));
        } else {
            $this->addFlash('warning', 'Impossible de récupérer les statistiques pour ce skill.');
        }

        return $this->redirectToRoute('app_admin_skills_list');
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, int $id): Response
    {
        $skill = $this->skillRepository->find($id);
        if (!$skill) {
            $this->addFlash('error', 'Compétence introuvable.');
            return $this->redirectToRoute('app_admin_skills_list');
        }

        $token = (string) $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_skill_' . $id, $token)) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_admin_skills_list');
        }

        $this->entityManager->remove($skill);
        $this->entityManager->flush();
        $this->addFlash('success', 'Compétence supprimée.');

        return $this->redirectToRoute('app_admin_skills_list');
    }
}
