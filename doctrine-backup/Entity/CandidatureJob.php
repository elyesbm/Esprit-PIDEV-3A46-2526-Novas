<?php

namespace App\Entity;

use App\Enum\CandidatureStatus;
use App\Enum\ModerationStatus;
use App\Repository\CandidatureJobRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[Vich\Uploadable]
#[ORM\Entity(repositoryClass: CandidatureJobRepository::class)]
#[ORM\Table(name: 'candidature_job')]
#[ORM\UniqueConstraint(name: 'uniq_offre_candidat', columns: ['offre_id', 'candidat_id'])]
#[ORM\Index(name: 'idx_offre', columns: ['offre_id'])]
#[ORM\Index(name: 'idx_candidat', columns: ['candidat_id'])]
class CandidatureJob
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_candidature')]
    private ?int $id = null;

    #[ORM\Column(name: 'message', type: Types::TEXT, length: 65535, nullable: true)]
    private ?string $message_candidature = null;

    #[ORM\Column(
        name: 'statut',
        type: Types::ENUM,
        enumType: CandidatureStatus::class,
        options: ['values' => ['EN_ATTENTE', 'ACCEPTEE', 'REFUSEE'], 'default' => 'EN_ATTENTE']
    )]
    private ?CandidatureStatus $statut_candidature = null;

    #[ORM\Column(name: 'date_candidature', type: Types::DATETIME_MUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $date_candidature = null;

    #[ORM\ManyToOne(targetEntity: Offrejob::class, inversedBy: 'candidatures')]
    #[ORM\JoinColumn(name: 'offre_id', referencedColumnName: 'id_offre', nullable: false, onDelete: 'CASCADE')]
    private ?Offrejob $offre = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'candidatureJobs')]
    #[ORM\JoinColumn(name: 'candidat_id', referencedColumnName: 'ID', nullable: false, onDelete: 'CASCADE')]
    private ?User $candidat = null;

    #[ORM\Column(name: 'cv_name', length: 255, nullable: true)]
    private ?string $cvName = null;

    #[Vich\UploadableField(mapping: 'candidature_cv', fileNameProperty: 'cvName')]
    #[Assert\File(
        maxSize: '2M',
        mimeTypes: ['application/pdf', 'application/x-pdf'],
        mimeTypesMessage: 'Veuillez televerser un fichier PDF valide.',
        maxSizeMessage: 'Le CV ne doit pas depasser 2 Mo.'
    )]
    private ?File $cvFile = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(
        name: 'moderation_status',
        type: Types::ENUM,
        enumType: ModerationStatus::class,
        options: ['values' => ['approved', 'pending', 'rejected'], 'default' => 'approved']
    )]
    private ModerationStatus $moderationStatus = ModerationStatus::APPROVED;

    public function getId(): ?int { return $this->id; }
    public function getMessageCandidature(): ?string { return $this->message_candidature; }
    public function setMessageCandidature(string $message_candidature): static { $this->message_candidature = $message_candidature; return $this; }
    public function getStatutCandidature(): ?string { return $this->statut_candidature?->value; }
    public function getStatutCandidatureEnum(): ?CandidatureStatus { return $this->statut_candidature; }
    public function setStatutCandidature(CandidatureStatus|string $statut_candidature): static
    {
        $this->statut_candidature = $statut_candidature instanceof CandidatureStatus
            ? $statut_candidature
            : CandidatureStatus::from($statut_candidature);
        return $this;
    }
    public function getDateCandidature(): ?\DateTimeInterface { return $this->date_candidature; }
    public function setDateCandidature(\DateTimeInterface $date_candidature): static { $this->date_candidature = $date_candidature; return $this; }
    public function getOffre(): ?Offrejob { return $this->offre; }
    public function setOffre(?Offrejob $offre): static { $this->offre = $offre; return $this; }
    public function getCandidat(): ?User { return $this->candidat; }
    public function setCandidat(?User $candidat): static { $this->candidat = $candidat; return $this; }

    public function getCvName(): ?string
    {
        return $this->cvName;
    }

    public function setCvName(?string $cvName): static
    {
        $this->cvName = $cvName;
        return $this;
    }

    public function getCvFile(): ?File
    {
        return $this->cvFile;
    }

    public function setCvFile(?File $cvFile = null): static
    {
        $this->cvFile = $cvFile;

        if (null !== $cvFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
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
}
