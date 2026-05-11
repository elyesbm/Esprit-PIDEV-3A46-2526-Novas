<?php

namespace App\Service;

use App\Entity\CvProfile;
use App\Entity\ScoreHistory;
use App\Entity\User;
use App\Repository\ScoreHistoryRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service central pour enregistrer les changements de score et construire
 * les données de graphe pour le tableau de bord admin.
 */
class ScoreHistoryService
{
    public function __construct(
        private EntityManagerInterface  $em,
        private ScoreHistoryRepository  $scoreHistoryRepo,
    ) {}

    // ─── Enregistrement d'événements ─────────────────────────────────────────

    /**
     * Enregistre un changement de score suite à une analyse CV.
     */
    public function recordCvAnalysis(User $user, CvProfile $oldProfile, CvProfile $newProfile): ScoreHistory
    {
        return $this->record(
            user:          $user,
            actionType:    ScoreHistory::ACTION_CV_UPLOAD,
            actionDetail:  $newProfile->getCvFileName(),
            oldCv:         $oldProfile->getCvScore(),
            newCv:         $newProfile->getCvScore(),
            oldMaturity:   $oldProfile->getMaturityScore(),
            newMaturity:   $newProfile->getMaturityScore(),
            oldCompet:     $oldProfile->getCompetitivenessIndex(),
            newCompet:     $newProfile->getCompetitivenessIndex(),
        );
    }

    /**
     * Enregistre un changement de score suite à l'ajout d'un skill.
     */
    public function recordSkillAdded(User $user, string $skillName): ScoreHistory
    {
        $profile = $user->getCvProfile();
        $current = $profile?->getCvScore() ?? 0;

        // L'ajout d'un skill booste légèrement le score CV (simulé si pas d'IA)
        $newScore = min($current + 3, 100);

        return $this->record(
            user:         $user,
            actionType:   ScoreHistory::ACTION_SKILL_ADDED,
            actionDetail: $skillName,
            oldCv:        $current,
            newCv:        $newScore,
            oldMaturity:  $profile?->getMaturityScore(),
            newMaturity:  $profile ? min(($profile->getMaturityScore() ?? 0) + 2, 100) : null,
            oldCompet:    $profile?->getCompetitivenessIndex(),
            newCompet:    $profile ? min(($profile->getCompetitivenessIndex() ?? 0) + 2, 100) : null,
        );
    }

    /**
     * Enregistre un changement de score suite à la complétion d'un atelier.
     */
    public function recordAtelierCompleted(User $user, string $atelierTitle): ScoreHistory
    {
        $profile = $user->getCvProfile();
        $current = $profile?->getCvScore() ?? 0;

        $newScore = min($current + 5, 100);

        return $this->record(
            user:         $user,
            actionType:   ScoreHistory::ACTION_ATELIER_DONE,
            actionDetail: $atelierTitle,
            oldCv:        $current,
            newCv:        $newScore,
            oldMaturity:  $profile?->getMaturityScore(),
            newMaturity:  $profile ? min(($profile->getMaturityScore() ?? 0) + 4, 100) : null,
            oldCompet:    $profile?->getCompetitivenessIndex(),
            newCompet:    $profile ? min(($profile->getCompetitivenessIndex() ?? 0) + 3, 100) : null,
        );
    }

    /**
     * Enregistre un changement de score suite à une mise à jour de profil.
     */
    public function recordProfileUpdate(User $user): ScoreHistory
    {
        $profile = $user->getCvProfile();
        $current = $profile?->getCvScore() ?? 0;

        return $this->record(
            user:         $user,
            actionType:   ScoreHistory::ACTION_PROFILE_UPDATE,
            actionDetail: null,
            oldCv:        $current,
            newCv:        min($current + 1, 100),
            oldMaturity:  $profile?->getMaturityScore(),
            newMaturity:  $profile?->getMaturityScore(),
            oldCompet:    $profile?->getCompetitivenessIndex(),
            newCompet:    $profile?->getCompetitivenessIndex(),
        );
    }

    // ─── Données pour les graphes ─────────────────────────────────────────────

    /**
     * Construit le dataset complet pour Chart.js — 7 jours d'historique.
     *
     * @return array{
     *   labels: list<string>,
     *   datasets: array{
     *     cv_score: list<float|null>,
     *     maturity_score: list<float|null>,
     *     competitiveness_index: list<float|null>,
     *     events: list<int>
     *   },
     *   stats: array<string, mixed>
     * }
     */
    public function buildChartData(int $days = 7): array
    {
        $rawData = $this->scoreHistoryRepo->getDailyAveragesForLastDays($days);

        // Construire une map date => données pour combler les trous
        $dataMap = [];
        foreach ($rawData as $row) {
            $dataMap[$row['day']] = $row;
        }

        $labels             = [];
        $avgCv              = [];
        $avgMaturity        = [];
        $avgCompetitiveness = [];
        $events             = [];

        $dayLabels = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date   = (new \DateTime("-{$i} days"))->format('Y-m-d');
            $dayKey = (new \DateTime("-{$i} days"))->format('d/m');
            $dow    = (int) (new \DateTime("-{$i} days"))->format('w');

            $labels[]             = $dayLabels[$dow] . ' ' . $dayKey;
            $avgCv[]              = isset($dataMap[$date]) ? round((float) $dataMap[$date]['avg_cv'], 1)              : null;
            $avgMaturity[]        = isset($dataMap[$date]) ? round((float) $dataMap[$date]['avg_maturity'], 1)       : null;
            $avgCompetitiveness[] = isset($dataMap[$date]) ? round((float) $dataMap[$date]['avg_competitiveness'], 1): null;
            $events[]             = isset($dataMap[$date]) ? (int) $dataMap[$date]['events']                         : 0;
        }

        $globalStats = $this->scoreHistoryRepo->getGlobalStats($days);

        // Calculer la tendance : comparaison 1ère moitié vs 2ème moitié
        $validCv = array_values(array_filter($avgCv, static fn (?float $v): bool => $v !== null));
        $trend   = $this->computeTrend($validCv);

        return [
            'labels'   => $labels,
            'datasets' => [
                'cv_score'             => $avgCv,
                'maturity_score'       => $avgMaturity,
                'competitiveness_index'=> $avgCompetitiveness,
                'events'               => $events,
            ],
            'stats'    => array_merge($globalStats, [
                'trend'        => $trend,
                'trend_label'  => $trend > 0 ? '+' . $trend . '%' : $trend . '%',
                'trend_up'     => $trend >= 0,
            ]),
        ];
    }

    // ─── Privé ───────────────────────────────────────────────────────────────

    private function record(
        User    $user,
        string  $actionType,
        ?string $actionDetail,
        ?int    $oldCv,
        ?int    $newCv,
        ?int    $oldMaturity,
        ?int    $newMaturity,
        ?int    $oldCompet,
        ?int    $newCompet,
    ): ScoreHistory {
        $entry = new ScoreHistory();
        $entry->setUser($user)
              ->setActionType($actionType)
              ->setActionDetail($actionDetail)
              ->setOldCvScore($oldCv)
              ->setNewCvScore($newCv)
              ->setOldMaturityScore($oldMaturity)
              ->setNewMaturityScore($newMaturity)
              ->setOldCompetitivenessIndex($oldCompet)
              ->setNewCompetitivenessIndex($newCompet);

        $this->em->persist($entry);
        $this->em->flush();

        return $entry;
    }

    /**
     * Calcule le % de variation entre la 1ère et 2ème moitié de la période.
     *
     * @param list<float|int> $values
     */
    private function computeTrend(array $values): float
    {
        if (count($values) < 2) return 0;

        $mid    = (int) ceil(count($values) / 2);
        $first  = array_slice($values, 0, $mid);
        $second = array_slice($values, $mid);

        $avgFirst  = count($first)  > 0 ? array_sum($first)  / count($first)  : 0;
        $avgSecond = count($second) > 0 ? array_sum($second) / count($second) : 0;

        if ($avgFirst == 0) return 0;

        return round((($avgSecond - $avgFirst) / $avgFirst) * 100, 1);
    }
}
