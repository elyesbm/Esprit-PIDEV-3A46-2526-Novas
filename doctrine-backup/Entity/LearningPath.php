<?php

namespace App\Entity;

use App\Repository\LearningPathRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LearningPathRepository::class)]
#[ORM\HasLifecycleCallbacks]
class LearningPath
{
    public const TYPE_POST = 'post';
    public const TYPE_VIDEO = 'video';
    public const TYPE_EXERCICE = 'exercice';
    public const TYPE_QUIZ = 'quiz';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_learning')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $titre_path = null;

    #[ORM\Column(length: 255)]
    private ?string $description_skill = null;

    #[ORM\Column]
    private ?int $duree_estimee = null;

    #[ORM\Column]
    private ?int $contexte_path = null;

    #[ORM\Column]
    private ?int $statut_path = null;

    #[ORM\Column]
    private ?int $niveau_path = null;

    /** Type de contenu : post, video, exercice, quiz */
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $type_etape = null;

    /** URL vers la ressource (vidéo, lien externe, etc.) */
    #[ORM\Column(length: 500, nullable: true)]
    private ?string $url = null;

    #[ORM\ManyToOne(targetEntity: Skill::class, inversedBy: 'learningPaths')]
    #[ORM\JoinColumn(name: 'id_skill', referencedColumnName: 'id_skill', nullable: false)]
    private ?Skill $skill = null;

    public function getId(): ?int { return $this->id; }
    public function getTitrePath(): ?string { return $this->titre_path; }
    public function setTitrePath(string $titre_path): static { $this->titre_path = $titre_path; return $this; }
    public function getDescriptionSkill(): ?string { return $this->description_skill; }
    public function setDescriptionSkill(?string $description_skill): static { $this->description_skill = $description_skill ?? ''; return $this; }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function ensureDescriptionNotNull(): void
    {
        if ($this->description_skill === null) {
            $this->description_skill = '';
        }
    }
    public function getDureeEstimee(): ?int { return $this->duree_estimee; }
    public function setDureeEstimee(int $duree_estimee): static { $this->duree_estimee = $duree_estimee; return $this; }
    public function getContextePath(): ?int { return $this->contexte_path; }
    public function setContextePath(int $contexte_path): static { $this->contexte_path = $contexte_path; return $this; }
    public function getStatutPath(): ?int { return $this->statut_path; }
    public function setStatutPath(int $statut_path): static { $this->statut_path = $statut_path; return $this; }
    public function getNiveauPath(): ?int { return $this->niveau_path; }
    public function setNiveauPath(int $niveau_path): static { $this->niveau_path = $niveau_path; return $this; }
    public function getSkill(): ?Skill { return $this->skill; }
    public function setSkill(?Skill $skill): static { $this->skill = $skill; return $this; }

    public function getTypeEtape(): ?string { return $this->type_etape; }
    public function setTypeEtape(?string $type_etape): static { $this->type_etape = $type_etape; return $this; }

    public function getUrl(): ?string { return $this->url; }
    public function setUrl(?string $url): static { $this->url = $url; return $this; }
}