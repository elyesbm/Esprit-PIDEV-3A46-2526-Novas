<?php

namespace App\Entity;

use App\Enum\OffreCategorie;
use App\Enum\OffreLieu;
use App\Enum\ModerationStatus;
use App\Enum\OffreStatut;
use App\Repository\OffrejobRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: OffrejobRepository::class)]
class Offrejob
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_offre')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $titre_offre = null;

    #[ORM\Column(type: Types::TEXT, length: 65535)]
    private ?string $description_offre = null;

    #[ORM\Column(
        type: Types::ENUM,
        enumType: OffreCategorie::class,
        options: ['values' => ['TUTORAT', 'CREATION', 'AIDE']]
    )]
    private ?OffreCategorie $categorie_offre = null;

    #[ORM\Column(
        type: Types::ENUM,
        enumType: OffreLieu::class,
        options: ['values' => ['EN_LIGNE', 'PRESENTIEL']]
    )]
    private ?OffreLieu $lieu = null;

    #[ORM\Column(
        type: Types::ENUM,
        enumType: OffreStatut::class,
        options: ['values' => ['OUVERTE', 'FERMEE', 'EXPIREE'], 'default' => 'OUVERTE']
    )]
    private ?OffreStatut $statut_offre = null;

    #[ORM\Column(name: 'capacite_max', type: Types::INTEGER, options: ['default' => 5])]
    #[Assert\Positive(message: 'La capacite maximale doit etre superieure a 0.')]
    private int $capacite_max = 5;

    #[ORM\Column(name: 'capacite_restante', type: Types::INTEGER, options: ['default' => 5])]
    #[Assert\GreaterThanOrEqual(value: 0, message: 'La capacite restante ne peut pas etre negative.')]
    private int $capacite_restante = 5;

    #[ORM\Column(name: 'date_expiration', type: Types::DATETIME_IMMUTABLE)]
    #[Assert\GreaterThan('now', message: 'La date d expiration doit etre dans le futur.')]
    private ?\DateTimeImmutable $date_expiration = null;

    #[ORM\Column(
        name: 'moderation_status',
        type: Types::ENUM,
        enumType: ModerationStatus::class,
        options: ['values' => ['approved', 'pending', 'rejected'], 'default' => 'approved']
    )]
    private ModerationStatus $moderationStatus = ModerationStatus::APPROVED;

    #[ORM\Column(name: 'adresse', length: 255, nullable: true)]
    private ?string $adresse = null;

    #[ORM\Column(name: 'latitude', type: Types::FLOAT, nullable: true)]
    private ?float $latitude = null;

    #[ORM\Column(name: 'longitude', type: Types::FLOAT, nullable: true)]
    private ?float $longitude = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $date_creation_offre = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'offrejobs')]
    #[ORM\JoinColumn(name: 'createur_id', referencedColumnName: 'ID', nullable: false, onDelete: 'CASCADE')]
    private ?User $createur = null;

    #[ORM\OneToMany(mappedBy: 'offre', targetEntity: CandidatureJob::class)]
    private Collection $candidatures;

    public function __construct()
    {
        $this->candidatures = new ArrayCollection();
        $this->date_expiration = (new \DateTimeImmutable())->modify('+30 days');
    }

    public function getId(): ?int { return $this->id; }
    public function getTitreOffre(): ?string { return $this->titre_offre; }
    public function setTitreOffre(string $titre_offre): static { $this->titre_offre = $titre_offre; return $this; }
    public function getDescriptionOffre(): ?string { return $this->description_offre; }
    public function setDescriptionOffre(string $description_offre): static { $this->description_offre = $description_offre; return $this; }
    public function getCategorieOffre(): ?string { return $this->categorie_offre?->value; }
    public function getCategorieOffreEnum(): ?OffreCategorie { return $this->categorie_offre; }
    public function setCategorieOffre(OffreCategorie|string $categorie_offre): static
    {
        $this->categorie_offre = $categorie_offre instanceof OffreCategorie
            ? $categorie_offre
            : OffreCategorie::from($categorie_offre);
        return $this;
    }
    public function getLieu(): ?string { return $this->lieu?->value; }
    public function getLieuEnum(): ?OffreLieu { return $this->lieu; }
    public function setLieu(OffreLieu|string $lieu): static
    {
        $this->lieu = $lieu instanceof OffreLieu ? $lieu : OffreLieu::from($lieu);
        return $this;
    }
    public function getStatutOffre(): ?string { return $this->statut_offre?->value; }
    public function getStatutOffreEnum(): ?OffreStatut { return $this->statut_offre; }
    public function setStatutOffre(OffreStatut|string $statut_offre): static
    {
        $this->statut_offre = $statut_offre instanceof OffreStatut
            ? $statut_offre
            : OffreStatut::from($statut_offre);
        return $this;
    }
    public function getCapaciteMax(): int
    {
        return $this->capacite_max;
    }
    public function setCapaciteMax(int $capacite_max): static
    {
        if ($capacite_max <= 0) {
            throw new \InvalidArgumentException('La capacite maximale doit etre superieure a 0.');
        }

        $this->capacite_max = $capacite_max;
        return $this;
    }
    public function getCapaciteRestante(): int
    {
        return $this->capacite_restante;
    }
    public function setCapaciteRestante(int $capacite_restante): static
    {
        if ($capacite_restante < 0) {
            throw new \InvalidArgumentException('La capacite restante ne peut pas etre negative.');
        }

        $this->capacite_restante = $capacite_restante;
        return $this;
    }
    public function isComplet(): bool
    {
        return $this->capacite_restante <= 0;
    }
    public function getDateExpiration(): ?\DateTimeImmutable
    {
        return $this->date_expiration;
    }
    public function setDateExpiration(\DateTimeImmutable $date_expiration): static
    {
        $this->date_expiration = $date_expiration;
        return $this;
    }
    public function isExpired(): bool
    {
        return $this->date_expiration !== null && $this->date_expiration <= new \DateTimeImmutable();
    }
    public function getModerationStatus(): string
    {
        return $this->moderationStatus->value;
    }
    public function getModerationStatusEnum(): ModerationStatus
    {
        return $this->moderationStatus;
    }
    public function setModerationStatus(ModerationStatus|string $moderationStatus): static
    {
        $this->moderationStatus = $moderationStatus instanceof ModerationStatus
            ? $moderationStatus
            : ModerationStatus::from($moderationStatus);

        return $this;
    }
    public function isModerationApproved(): bool
    {
        return $this->moderationStatus === ModerationStatus::APPROVED;
    }
    public function isModerationPending(): bool
    {
        return $this->moderationStatus === ModerationStatus::PENDING;
    }
    public function isModerationRejected(): bool
    {
        return $this->moderationStatus === ModerationStatus::REJECTED;
    }
    public function getAdresse(): ?string
    {
        return $this->adresse;
    }
    public function setAdresse(?string $adresse): static
    {
        $this->adresse = $adresse !== null ? trim($adresse) : null;
        if ($this->adresse === '') {
            $this->adresse = null;
        }
        return $this;
    }
    public function getLatitude(): ?float
    {
        return $this->latitude;
    }
    public function setLatitude(?float $latitude): static
    {
        $this->latitude = $latitude;
        return $this;
    }
    public function getLongitude(): ?float
    {
        return $this->longitude;
    }
    public function setLongitude(?float $longitude): static
    {
        $this->longitude = $longitude;
        return $this;
    }
    public function getDateCreationOffre(): ?\DateTimeInterface { return $this->date_creation_offre; }
    public function setDateCreationOffre(\DateTimeInterface $date_creation_offre): static { $this->date_creation_offre = $date_creation_offre; return $this; }
    public function getCreateur(): ?User { return $this->createur; }
    public function setCreateur(?User $createur): static { $this->createur = $createur; return $this; }
    public function getCandidatures(): Collection { return $this->candidatures; }

    #[Assert\Callback]
    public function validateAdresseForPresentiel(ExecutionContextInterface $context): void
    {
        if ($this->getLieu() !== OffreLieu::PRESENTIEL->value) {
            return;
        }

        if ($this->adresse === null || trim($this->adresse) === '') {
            $context->buildViolation('L adresse est obligatoire pour une offre en presentiel.')
                ->atPath('adresse')
                ->addViolation();
        }
    }
}
