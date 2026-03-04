<?php

namespace App\Entity;

use App\Repository\CvProfileRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CvProfileRepository::class)]
class CvProfile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'cvProfile')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE', referencedColumnName: 'ID')]
    private ?User $user = null;

    /** Chemin vers le fichier CV uploadé */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cvFilePath = null;

    /** Nom original du fichier */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cvFileName = null;

    /** Score CV global 0-100 */
    #[ORM\Column(nullable: true)]
    private ?int $cvScore = null;

    /** Score de maturité professionnelle 0-100 */
    #[ORM\Column(nullable: true)]
    private ?int $maturityScore = null;

    /** Indice de compétitivité marché 0-100 */
    #[ORM\Column(nullable: true)]
    private ?int $competitivenessIndex = null;

    /** Niveau estimé : junior / mid / senior / expert */
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $estimatedLevel = null;

    /** Domaine principal : web, data, devops, design, etc. */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $mainDomain = null;

    /** Compétences techniques extraites */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $technicalSkills = null;

    /** Soft skills extraits */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $softSkills = null;

    /** Points faibles identifiés */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $weakPoints = null;

    /** Recommandations personnalisées */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $recommendations = null;

    /** Simulation d'évolution : [scenario => score_prédit] */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $evolutionSimulation = null;

    /** Résumé professionnel généré par l'IA */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $aiSummary = null;

    /** Analyse brute complète de l'IA (stockage JSON) */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $rawAnalysis = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $analyzedAt = null;

    // ─── Getters / Setters ───────────────────────────────────────────────────

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getCvFilePath(): ?string { return $this->cvFilePath; }
    public function setCvFilePath(?string $cvFilePath): static { $this->cvFilePath = $cvFilePath; return $this; }

    public function getCvFileName(): ?string { return $this->cvFileName; }
    public function setCvFileName(?string $cvFileName): static { $this->cvFileName = $cvFileName; return $this; }

    public function getCvScore(): ?int { return $this->cvScore; }
    public function setCvScore(?int $cvScore): static { $this->cvScore = $cvScore; return $this; }

    public function getMaturityScore(): ?int { return $this->maturityScore; }
    public function setMaturityScore(?int $maturityScore): static { $this->maturityScore = $maturityScore; return $this; }

    public function getCompetitivenessIndex(): ?int { return $this->competitivenessIndex; }
    public function setCompetitivenessIndex(?int $idx): static { $this->competitivenessIndex = $idx; return $this; }

    public function getEstimatedLevel(): ?string { return $this->estimatedLevel; }
    public function setEstimatedLevel(?string $estimatedLevel): static { $this->estimatedLevel = $estimatedLevel; return $this; }

    public function getMainDomain(): ?string { return $this->mainDomain; }
    public function setMainDomain(?string $mainDomain): static { $this->mainDomain = $mainDomain; return $this; }

    public function getTechnicalSkills(): ?array { return $this->technicalSkills; }
    public function setTechnicalSkills(?array $technicalSkills): static { $this->technicalSkills = $technicalSkills; return $this; }

    public function getSoftSkills(): ?array { return $this->softSkills; }
    public function setSoftSkills(?array $softSkills): static { $this->softSkills = $softSkills; return $this; }

    public function getWeakPoints(): ?array { return $this->weakPoints; }
    public function setWeakPoints(?array $weakPoints): static { $this->weakPoints = $weakPoints; return $this; }

    public function getRecommendations(): ?array { return $this->recommendations; }
    public function setRecommendations(?array $recommendations): static { $this->recommendations = $recommendations; return $this; }

    public function getEvolutionSimulation(): ?array { return $this->evolutionSimulation; }
    public function setEvolutionSimulation(?array $evolutionSimulation): static { $this->evolutionSimulation = $evolutionSimulation; return $this; }

    public function getAiSummary(): ?string { return $this->aiSummary; }
    public function setAiSummary(?string $aiSummary): static { $this->aiSummary = $aiSummary; return $this; }

    public function getRawAnalysis(): ?array { return $this->rawAnalysis; }
    public function setRawAnalysis(?array $rawAnalysis): static { $this->rawAnalysis = $rawAnalysis; return $this; }

    public function getAnalyzedAt(): ?\DateTimeInterface { return $this->analyzedAt; }
    public function setAnalyzedAt(?\DateTimeInterface $analyzedAt): static { $this->analyzedAt = $analyzedAt; return $this; }

    public function hasBeenAnalyzed(): bool { return $this->cvScore !== null; }

    /** Couleur selon le score */
    public function getScoreColor(int $score): string
    {
        if ($score >= 80) return 'emerald';
        if ($score >= 60) return 'blue';
        if ($score >= 40) return 'amber';
        return 'rose';
    }

    /** Label du niveau */
    public function getLevelLabel(): string
    {
        return match($this->estimatedLevel) {
            'junior'  => 'Junior',
            'mid'     => 'Intermédiaire',
            'senior'  => 'Senior',
            'expert'  => 'Expert',
            default   => 'Non défini',
        };
    }
}
