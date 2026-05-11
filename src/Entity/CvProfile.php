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
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE', referencedColumnName: 'id')]
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
    /** @var list<string>|null */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $technicalSkills = null;

    /** Soft skills extraits */
    /** @var list<string>|null */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $softSkills = null;

    /** Points faibles identifiés */
    /** @var list<string>|null */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $weakPoints = null;

    /** Recommandations personnalisées */
    /** @var list<string>|null */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $recommendations = null;

    /** Simulation d'évolution : [scenario => score_prédit] */
    /** @var array<string, float|int>|null */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $evolutionSimulation = null;

    /** Résumé professionnel généré par l'IA */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $aiSummary = null;

    /** Analyse brute complète de l'IA (stockage JSON) */
    /** @var array<string, mixed>|null */
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

    /** @return list<string>|null */
    public function getTechnicalSkills(): ?array
    {
        if ($this->technicalSkills === null) {
            return null;
        }

        $skills = [];
        foreach ($this->technicalSkills as $skill) {
            if (\is_string($skill) && $skill !== '') {
                $skills[] = $skill;
            }
        }

        return $skills;
    }
    /** @param list<string>|null $technicalSkills */
    public function setTechnicalSkills(?array $technicalSkills): static
    {
        if ($technicalSkills === null) {
            $this->technicalSkills = null;
            return $this;
        }

        $skills = [];
        foreach (array_values($technicalSkills) as $skill) {
            if (\is_string($skill) && $skill !== '') {
                $skills[] = $skill;
            }
        }
        $this->technicalSkills = $skills;
        return $this;
    }

    /** @return list<string>|null */
    public function getSoftSkills(): ?array
    {
        if ($this->softSkills === null) {
            return null;
        }

        $skills = [];
        foreach ($this->softSkills as $skill) {
            if (\is_string($skill) && $skill !== '') {
                $skills[] = $skill;
            }
        }

        return $skills;
    }
    /** @param list<string>|null $softSkills */
    public function setSoftSkills(?array $softSkills): static
    {
        if ($softSkills === null) {
            $this->softSkills = null;
            return $this;
        }

        $skills = [];
        foreach (array_values($softSkills) as $skill) {
            if (\is_string($skill) && $skill !== '') {
                $skills[] = $skill;
            }
        }
        $this->softSkills = $skills;
        return $this;
    }

    /** @return list<string>|null */
    public function getWeakPoints(): ?array
    {
        if ($this->weakPoints === null) {
            return null;
        }

        $points = [];
        foreach ($this->weakPoints as $point) {
            if (\is_string($point) && $point !== '') {
                $points[] = $point;
            }
        }

        return $points;
    }
    /** @param list<string>|null $weakPoints */
    public function setWeakPoints(?array $weakPoints): static
    {
        if ($weakPoints === null) {
            $this->weakPoints = null;
            return $this;
        }

        $points = [];
        foreach (array_values($weakPoints) as $point) {
            if (\is_string($point) && $point !== '') {
                $points[] = $point;
            }
        }
        $this->weakPoints = $points;
        return $this;
    }

    /** @return list<string>|null */
    public function getRecommendations(): ?array
    {
        if ($this->recommendations === null) {
            return null;
        }

        $recommendations = [];
        foreach ($this->recommendations as $recommendation) {
            if (\is_string($recommendation) && $recommendation !== '') {
                $recommendations[] = $recommendation;
            }
        }

        return $recommendations;
    }
    /** @param list<string>|null $recommendations */
    public function setRecommendations(?array $recommendations): static
    {
        if ($recommendations === null) {
            $this->recommendations = null;
            return $this;
        }

        $items = [];
        foreach (array_values($recommendations) as $recommendation) {
            if (\is_string($recommendation) && $recommendation !== '') {
                $items[] = $recommendation;
            }
        }
        $this->recommendations = $items;
        return $this;
    }

    /** @return array<string, float|int>|null */
    public function getEvolutionSimulation(): ?array { return $this->evolutionSimulation; }
    /** @param array<string, float|int>|null $evolutionSimulation */
    public function setEvolutionSimulation(?array $evolutionSimulation): static { $this->evolutionSimulation = $evolutionSimulation; return $this; }

    public function getAiSummary(): ?string { return $this->aiSummary; }
    public function setAiSummary(?string $aiSummary): static { $this->aiSummary = $aiSummary; return $this; }

    /** @return array<string, mixed>|null */
    public function getRawAnalysis(): ?array { return $this->rawAnalysis; }
    /** @param array<string, mixed>|null $rawAnalysis */
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
