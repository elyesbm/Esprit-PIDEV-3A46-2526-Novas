<?php

namespace App\Repository;

use App\Entity\ScoreHistory;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ScoreHistory>
 */
class ScoreHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ScoreHistory::class);
    }

    /**
     * Retourne les moyennes journalières sur N jours (via SQL natif pour compatibilité Doctrine).
     *
     * @return array<int, array{day: string, avg_cv: float, avg_maturity: float, avg_competitiveness: float, events: int}>
     */
    public function getDailyAveragesForLastDays(int $days = 7): array
    {
        $since = (new \DateTime("-{$days} days midnight"))->format('Y-m-d H:i:s');

        $sql = "
            SELECT
                SUBSTRING(sh.recorded_at, 1, 10) AS day,
                AVG(sh.new_cv_score)              AS avg_cv,
                AVG(sh.new_maturity_score)        AS avg_maturity,
                AVG(sh.new_competitiveness_index) AS avg_competitiveness,
                COUNT(sh.id)                      AS events
            FROM score_history sh
            WHERE sh.recorded_at >= :since
            GROUP BY day
            ORDER BY day ASC
        ";

        $conn = $this->getEntityManager()->getConnection();
        $rows = $conn->fetchAllAssociative($sql, ['since' => $since]);

        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'day' => (string) ($row['day'] ?? ''),
                'avg_cv' => (float) ($row['avg_cv'] ?? 0.0),
                'avg_maturity' => (float) ($row['avg_maturity'] ?? 0.0),
                'avg_competitiveness' => (float) ($row['avg_competitiveness'] ?? 0.0),
                'events' => (int) ($row['events'] ?? 0),
            ];
        }

        return $out;
    }

    /**
     * Retourne les N derniers événements pour la table d'activité.
     *
     * @return ScoreHistory[]
     */
    public function findRecentEvents(int $limit = 15): array
    {
        return $this->createQueryBuilder('sh')
            ->select('sh', 'u')
            ->join('sh.user', 'u')
            ->orderBy('sh.recordedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques globales : total events, progression moyenne, top acteur, répartition par action.
     *
     * @return array{
     *   total_events: int,
     *   avg_delta: float,
     *   top_user: array<string, mixed>|null,
     *   by_action: list<array{actionType: string, cnt: int}>
     * }
     */
    public function getGlobalStats(int $days = 7): array
    {
        $since = (new \DateTime("-{$days} days midnight"))->format('Y-m-d H:i:s');
        $conn  = $this->getEntityManager()->getConnection();

        // Totaux
        $total = $conn->fetchAssociative("
            SELECT
                COUNT(id)                              AS total_events,
                AVG(new_cv_score - old_cv_score)       AS avg_delta
            FROM score_history
            WHERE recorded_at >= :since
        ", ['since' => $since]);

        // Top user — plus grande progression totale
        $topUser = $conn->fetchAssociative("
            SELECT u.NOM, u.PRENOM, SUM(sh.new_cv_score - sh.old_cv_score) AS total_gain
            FROM score_history sh
            JOIN user u ON u.ID = sh.user_id
            WHERE sh.recorded_at >= :since
            GROUP BY u.ID
            ORDER BY total_gain DESC
            LIMIT 1
        ", ['since' => $since]);

        // Répartition par type d'action
        $byActionRows = $conn->fetchAllAssociative("
            SELECT action_type AS actionType, COUNT(id) AS cnt
            FROM score_history
            WHERE recorded_at >= :since
            GROUP BY action_type
            ORDER BY cnt DESC
        ", ['since' => $since]);

        $byAction = [];
        foreach ($byActionRows as $row) {
            $byAction[] = [
                'actionType' => (string) ($row['actionType'] ?? ''),
                'cnt' => (int) ($row['cnt'] ?? 0),
            ];
        }

        return [
            'total_events' => (int)   ($total['total_events'] ?? 0),
            'avg_delta'    => round((float) ($total['avg_delta']    ?? 0), 1),
            'top_user'     => $topUser ?: null,
            'by_action'    => $byAction,
        ];
    }

    /**
     * Historique d'un utilisateur précis.
     *
     * @return ScoreHistory[]
     */
    public function findByUserOrderedDesc(User $user, int $limit = 30): array
    {
        return $this->createQueryBuilder('sh')
            ->where('sh.user = :user')
            ->setParameter('user', $user)
            ->orderBy('sh.recordedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
