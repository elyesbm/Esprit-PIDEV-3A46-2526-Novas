<?php

namespace App\Entity;

use App\Repository\SkillRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SkillRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Skill
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_skill')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom du skill est obligatoire.')]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: 'Le nom du skill doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le nom du skill ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $nom_skill = null;

    #[ORM\Column(length: 255)]
    #[Assert\Length(
        max: 255,
        maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $description_skill = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'La catégorie est obligatoire.')]
    private ?string $categorie = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le type de compétence est obligatoire.')]
    private ?string $contexte_skill = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'skills')]
    #[ORM\JoinColumn(name: 'createur_id', referencedColumnName: 'ID', nullable: true, onDelete: 'CASCADE')]
    private ?User $createur = null;

    #[ORM\OneToMany(mappedBy: 'skill', targetEntity: LearningPath::class)]
    private Collection $learningPaths;

    /** Nombre d'offres d'emploi associées à ce skill (source: API marché) */
    #[ORM\Column(name: 'nombre_offres_associees', nullable: true)]
    private ?int $nombreOffresAssociees = null;

    /** Score de demande sur le marché (1-5, 5 = très demandé) */
    #[ORM\Column(name: 'score_demande', nullable: true)]
    private ?int $scoreDemande = null;

    /** Tendance marché: en_hausse, stable, en_baisse */
    #[ORM\Column(name: 'tendance_marche', length: 20, nullable: true)]
    private ?string $tendanceMarche = null;

    /** Date de dernière mise à jour des statistiques marché */
    #[ORM\Column(name: 'date_mise_a_jour_stats', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $dateMiseAJourStats = null;

    public function __construct()
    {
        $this->learningPaths = new ArrayCollection();
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function ensureDescriptionNotNull(): void
    {
        if ($this->description_skill === null) {
            $this->description_skill = '';
        }
    }

    public function getId(): ?int { return $this->id; }
    public function getNomSkill(): ?string { return $this->nom_skill; }
    public function setNomSkill(string $nom_skill): static { $this->nom_skill = $nom_skill; return $this; }
    public function getDescriptionSkill(): ?string { return $this->description_skill; }
    public function setDescriptionSkill(?string $description_skill): static { $this->description_skill = $description_skill ?? ''; return $this; }
    public function getCategorie(): ?string { return $this->categorie; }
    public function setCategorie(string $categorie): static { $this->categorie = $categorie; return $this; }
    public function getContexteSkill(): ?string { return $this->contexte_skill; }
    public function setContexteSkill(string $contexte_skill): static { $this->contexte_skill = $contexte_skill; return $this; }
    public function getCreateur(): ?User { return $this->createur; }
    public function setCreateur(?User $createur): static { $this->createur = $createur; return $this; }
    public function getLearningPaths(): Collection { return $this->learningPaths; }

    public function getNombreOffresAssociees(): ?int { return $this->nombreOffresAssociees; }
    public function setNombreOffresAssociees(?int $nombreOffresAssociees): static { $this->nombreOffresAssociees = $nombreOffresAssociees; return $this; }

    public function getScoreDemande(): ?int { return $this->scoreDemande; }
    public function setScoreDemande(?int $scoreDemande): static { $this->scoreDemande = $scoreDemande; return $this; }

    public function getTendanceMarche(): ?string { return $this->tendanceMarche; }
    public function setTendanceMarche(?string $tendanceMarche): static { $this->tendanceMarche = $tendanceMarche; return $this; }

    public function getDateMiseAJourStats(): ?\DateTimeInterface { return $this->dateMiseAJourStats; }
    public function setDateMiseAJourStats(?\DateTimeInterface $dateMiseAJourStats): static { $this->dateMiseAJourStats = $dateMiseAJourStats; return $this; }
}