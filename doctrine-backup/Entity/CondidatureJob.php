<?php

namespace App\Entity;

use App\Repository\CondidatureJobRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CondidatureJobRepository::class)]
class CondidatureJob
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_condidature')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $message_condidature = null;

    #[ORM\Column(length: 50)]
    private ?string $statut_condidature = null;

    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $date_condidature = null;

    #[ORM\ManyToOne(targetEntity: Offrejob::class)]
    #[ORM\JoinColumn(name: 'offre_id', referencedColumnName: 'id_offre', nullable: false)]
    private ?Offrejob $offre = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'condidatureJobs')]
    #[ORM\JoinColumn(name: 'condidat_id', referencedColumnName: 'ID', nullable: false)]
    private ?User $condidat = null;

    public function getId(): ?int { return $this->id; }
    public function getMessageCondidature(): ?string { return $this->message_condidature; }
    public function setMessageCondidature(string $message_condidature): static { $this->message_condidature = $message_condidature; return $this; }
    public function getStatutCondidature(): ?string { return $this->statut_condidature; }
    public function setStatutCondidature(string $statut_condidature): static { $this->statut_condidature = $statut_condidature; return $this; }
    public function getDateCondidature(): ?\DateTimeInterface { return $this->date_condidature; }
    public function setDateCondidature(\DateTimeInterface $date_condidature): static { $this->date_condidature = $date_condidature; return $this; }
    public function getOffre(): ?Offrejob { return $this->offre; }
    public function setOffre(?Offrejob $offre): static { $this->offre = $offre; return $this; }
    public function getCondidat(): ?User { return $this->condidat; }
    public function setCondidat(?User $condidat): static { $this->condidat = $condidat; return $this; }
}
