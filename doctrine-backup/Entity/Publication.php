<?php

namespace App\Entity;

use App\Repository\PublicationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PublicationRepository::class)]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: false)]
class Publication
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_pub')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le titre est obligatoire.")]
#[Assert\Length(
    min: 3,
    max: 150,
    minMessage: "Le titre doit contenir au moins {{ limit }} caractères.",
    maxMessage: "Le titre ne doit pas dépasser {{ limit }} caractères."
)]
    private ?string $titre = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: "Le contenu est obligatoire.")]
#[Assert\Length(
    min: 5,
    max: 5000,
    minMessage: "Le contenu doit contenir au moins {{ limit }} caractères.",
    maxMessage: "Le contenu ne doit pas dépasser {{ limit }} caractères."
)]
    private ?string $contenu = null;

    #[ORM\Column(type: 'text')]
    private ?string $image_auteur = null;

    /** Image générée par Imagen (uploads/publication_images/) */
    #[ORM\Column(name: 'image_publication', type: 'string', length: 255, nullable: true)]
    private ?string $imagePublication = null;

    /** ID de la vidéo YouTube (optionnel), ex: dQw4w9WgXcQ */
    #[ORM\Column(name: 'youtube_video_id', type: 'string', length: 20, nullable: true)]
    private ?string $youtubeVideoId = null;

    #[ORM\Column]
    private ?int $statut = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $date_creation = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "Le contexte est obligatoire.")]
#[Assert\Choice(choices: [1, 2], message: "Contexte invalide.")]
private ?int $contexte = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'publications')]
    #[ORM\JoinColumn(name: 'id_auteur', referencedColumnName: 'ID', nullable: false)]
    private ?User $auteur = null;

    #[ORM\OneToMany(mappedBy: 'publication', targetEntity: Commentaire::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $commentaires;

    public function __construct()
    {
        $this->commentaires = new ArrayCollection();
    }

    #[ORM\Column(type: 'integer')]
    private int $likes = 0;

    #[ORM\Column(name: 'report_count', type: 'integer')]
    private int $reportCount = 0;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $date_modification = null;

    #[ORM\Column(name: 'deleted_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $deletedAt = null;

    public function getLikes(): int
    {
        return $this->likes;
    }

    public function setLikes(int $likes): static
    {
        $this->likes = max(0, $likes);
        return $this;
    }

    public function incrementLikes(): static
    {
        $this->likes++;
        return $this;
    }

    public function decrementLikes(): static
    {
        if ($this->likes > 0) {
            $this->likes--;
        }
        return $this;
    }

    public function getReportCount(): int
    {
        return $this->reportCount;
    }

    public function setReportCount(int $reportCount): static
    {
        $this->reportCount = max(0, $reportCount);
        return $this;
    }

    public function incrementReportCount(int $delta = 1): static
    {
        if ($delta > 0) {
            $this->reportCount += $delta;
        }
        return $this;
    }

    public function getDateModification(): ?\DateTimeInterface
    {
        return $this->date_modification;
    }

    public function setDateModification(?\DateTimeInterface $date): self
    {
        $this->date_modification = $date;
        return $this;
    }

    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeInterface $deletedAt): static
    {
        $this->deletedAt = $deletedAt;
        return $this;
    }

    public function getId(): ?int { return $this->id; }
    public function getTitre(): ?string { return $this->titre; }
    public function setTitre(?string $titre): static { $this->titre = $titre; return $this; }
    public function getContenu(): ?string { return $this->contenu; }
    public function setContenu(?string $contenu): static { $this->contenu = $contenu; return $this; }
    public function getImageAuteur(): ?string { return $this->image_auteur; }
    public function setImageAuteur(string $image_auteur): static { $this->image_auteur = $image_auteur; return $this; }
    public function getImagePublication(): ?string { return $this->imagePublication; }
    public function setImagePublication(?string $imagePublication): static { $this->imagePublication = $imagePublication; return $this; }
    public function getYoutubeVideoId(): ?string { return $this->youtubeVideoId; }
    public function setYoutubeVideoId(?string $youtubeVideoId): static { $this->youtubeVideoId = $youtubeVideoId; return $this; }
    public function getStatut(): ?int { return $this->statut; }
    public function setStatut(int $statut): static { $this->statut = $statut; return $this; }
    public function getDateCreation(): ?\DateTimeInterface { return $this->date_creation; }
    public function setDateCreation(\DateTimeInterface $date_creation): static { $this->date_creation = $date_creation; return $this; }
    public function getContexte(): ?int { return $this->contexte; }
    public function setContexte(?int $contexte): static { $this->contexte = $contexte; return $this; }
    public function getAuteur(): ?User { return $this->auteur; }
    public function setAuteur(?User $auteur): static { $this->auteur = $auteur; return $this; }
    public function getCommentaires(): Collection { return $this->commentaires; }
}
