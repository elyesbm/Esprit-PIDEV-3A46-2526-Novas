<?php

namespace App\Entity;

use App\Repository\PublicationReactionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PublicationReactionRepository::class)]
#[ORM\Table(name: 'publication_reaction')]
#[ORM\UniqueConstraint(name: 'uniq_pub_user_reaction', columns: ['id_pub', 'id_user'])]
class PublicationReaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_reaction')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Publication::class)]
    #[ORM\JoinColumn(name: 'id_pub', referencedColumnName: 'id_pub', nullable: false, onDelete: 'CASCADE')]
    private ?Publication $publication = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'ID', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(type: 'smallint')]
    #[Assert\Choice(choices: [1, 2], message: 'Reaction invalide.')]
    private ?int $value = null;

    #[ORM\Column(name: 'date_creation', type: 'datetime')]
    private ?\DateTimeInterface $dateCreation = null;

    /** Cause du signalement (lorsque value = 2), ex: spam, harassment */
    #[ORM\Column(name: 'signal_reason', type: 'string', length: 80, nullable: true)]
    private ?string $signalReason = null;

    public function __construct()
    {
        $this->dateCreation = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPublication(): ?Publication
    {
        return $this->publication;
    }

    public function setPublication(?Publication $publication): static
    {
        $this->publication = $publication;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getValue(): ?int
    {
        return $this->value;
    }

    public function setValue(?int $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeInterface $dateCreation): static
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    public function getSignalReason(): ?string
    {
        return $this->signalReason;
    }

    public function setSignalReason(?string $signalReason): static
    {
        $this->signalReason = $signalReason;

        return $this;
    }
}

