<?php

namespace App\Entity;

use App\Repository\ScoreHistoryRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Enregistre l'historique de l'évolution des scores utilisateurs.
 * Chaque entrée capture : qui, quelle action, quel changement de score, quand.
 */
#[ORM\Entity(repositoryClass: ScoreHistoryRepository::class)]
#[ORM\Table(name: 'score_history')]
#[ORM\Index(columns: ['recorded_at'], name: 'idx_recorded_at')]
#[ORM\Index(columns: ['user_id'], name: 'idx_user_id')]
class ScoreHistory
{
    // ─── Types d'actions déclenchant un changement de score ─────────────────
    public const ACTION_CV_UPLOAD       = 'cv_upload';
    public const ACTION_SKILL_ADDED     = 'skill_added';
    public const ACTION_ATELIER_DONE    = 'atelier_completed';
    public const ACTION_PROFILE_UPDATE  = 'profile_updated';
    public const ACTION_MANUAL          = 'manual';

    public const ACTION_LABELS = [
        self::ACTION_CV_UPLOAD      => 'Analyse CV',
        self::ACTION_SKILL_ADDED    => 'Compétence ajoutée',
        self::ACTION_ATELIER_DONE   => 'Atelier terminé',
        self::ACTION_PROFILE_UPDATE => 'Profil mis à jour',
        self::ACTION_MANUAL         => 'Mise à jour manuelle',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'ID', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    /** Score CV global (0-100) avant l'action */
    #[ORM\Column(nullable: true)]
    private ?int $oldCvScore = null;

    /** Score CV global (0-100) après l'action */
    #[ORM\Column(nullable: true)]
    private ?int $newCvScore = null;

    /** Score de maturité avant */
    #[ORM\Column(nullable: true)]
    private ?int $oldMaturityScore = null;

    /** Score de maturité après */
    #[ORM\Column(nullable: true)]
    private ?int $newMaturityScore = null;

    /** Indice de compétitivité avant */
    #[ORM\Column(nullable: true)]
    private ?int $oldCompetitivenessIndex = null;

    /** Indice de compétitivité après */
    #[ORM\Column(nullable: true)]
    private ?int $newCompetitivenessIndex = null;

    /** Type d'action déclencheur */
    #[ORM\Column(length: 60)]
    private string $actionType = self::ACTION_MANUAL;

    /** Détail optionnel (ex: nom du skill, titre atelier) */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $actionDetail = null;

    /** Date d'enregistrement */
    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $recordedAt;

    public function __construct()
    {
        $this->recordedAt = new \DateTime();
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /** Variation du score CV */
    public function getCvScoreDelta(): int
    {
        return ($this->newCvScore ?? 0) - ($this->oldCvScore ?? 0);
    }

    /** Variation de la maturité */
    public function getMaturityDelta(): int
    {
        return ($this->newMaturityScore ?? 0) - ($this->oldMaturityScore ?? 0);
    }

    /** Score CV composite moyen (pour les graphes) */
    public function getAverageNewScore(): float
    {
        $scores = array_filter([
            $this->newCvScore,
            $this->newMaturityScore,
            $this->newCompetitivenessIndex,
        ], fn($v) => $v !== null);

        return count($scores) > 0 ? array_sum($scores) / count($scores) : 0;
    }

    public function getActionLabel(): string
    {
        return self::ACTION_LABELS[$this->actionType] ?? $this->actionType;
    }

    // ─── Getters / Setters ────────────────────────────────────────────────────

    public function getId(): ?int                    { return $this->id; }

    public function getUser(): ?User                 { return $this->user; }
    public function setUser(?User $user): static     { $this->user = $user; return $this; }

    public function getOldCvScore(): ?int            { return $this->oldCvScore; }
    public function setOldCvScore(?int $v): static   { $this->oldCvScore = $v; return $this; }

    public function getNewCvScore(): ?int            { return $this->newCvScore; }
    public function setNewCvScore(?int $v): static   { $this->newCvScore = $v; return $this; }

    public function getOldMaturityScore(): ?int      { return $this->oldMaturityScore; }
    public function setOldMaturityScore(?int $v): static { $this->oldMaturityScore = $v; return $this; }

    public function getNewMaturityScore(): ?int      { return $this->newMaturityScore; }
    public function setNewMaturityScore(?int $v): static { $this->newMaturityScore = $v; return $this; }

    public function getOldCompetitivenessIndex(): ?int   { return $this->oldCompetitivenessIndex; }
    public function setOldCompetitivenessIndex(?int $v): static { $this->oldCompetitivenessIndex = $v; return $this; }

    public function getNewCompetitivenessIndex(): ?int   { return $this->newCompetitivenessIndex; }
    public function setNewCompetitivenessIndex(?int $v): static { $this->newCompetitivenessIndex = $v; return $this; }

    public function getActionType(): string          { return $this->actionType; }
    public function setActionType(string $v): static { $this->actionType = $v; return $this; }

    public function getActionDetail(): ?string       { return $this->actionDetail; }
    public function setActionDetail(?string $v): static { $this->actionDetail = $v; return $this; }

    public function getRecordedAt(): \DateTimeInterface       { return $this->recordedAt; }
    public function setRecordedAt(\DateTimeInterface $v): static { $this->recordedAt = $v; return $this; }
}
