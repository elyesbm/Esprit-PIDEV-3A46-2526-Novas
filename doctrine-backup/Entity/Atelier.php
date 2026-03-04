<?php

namespace App\Entity;

use App\Repository\AtelierRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AtelierRepository::class)]
class Atelier
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_atelier')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le titre de l'atelier est obligatoire.")]
    #[Assert\Length(min: 2, max: 255)]
    private ?string $titre_atelier = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "La description est obligatoire.")]
    #[Assert\Length(min: 10, max: 255)]
    private ?string $description_atelier = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le type d'atelier est obligatoire.")]
    private ?string $type_atelier = null;

    #[ORM\Column(type: 'date')]
    #[Assert\NotBlank(message: "La date de l'atelier est obligatoire.")]
    #[Assert\Type(\DateTimeInterface::class)]
    private ?\DateTimeInterface $date_atelier = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "La capacité est obligatoire.")]
    #[Assert\Range(min: 1, max: 9999)]
    private ?int $capacite = null;

    #[ORM\Column]
    private ?int $statut_atelier = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: "L'image (URL) est obligatoire.")]
    #[Assert\Length(max: 2000)]
    private ?string $image_atelier = null;

    #[ORM\Column]
    #[Assert\NotNull(message: "Le contexte est obligatoire.")]
    #[Assert\Choice(choices: [0, 1])]
    private ?int $contexte_atelier = null;

    #[ORM\OneToMany(mappedBy: 'atelier', targetEntity: Reservation::class)]
    private Collection $reservations;

    public function __construct()
    {
        $this->reservations = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getTitreAtelier(): ?string { return $this->titre_atelier; }
    public function setTitreAtelier(?string $titre_atelier): static
    {
        $this->titre_atelier = $titre_atelier;
        return $this;
    }

    public function getDescriptionAtelier(): ?string { return $this->description_atelier; }
    public function setDescriptionAtelier(?string $description_atelier): static
    {
        $this->description_atelier = $description_atelier;
        return $this;
    }

    public function getTypeAtelier(): ?string { return $this->type_atelier; }
    public function setTypeAtelier(?string $type_atelier): static
    {
        $this->type_atelier = $type_atelier;
        return $this;
    }

    public function getDateAtelier(): ?\DateTimeInterface { return $this->date_atelier; }
    public function setDateAtelier(?\DateTimeInterface $date_atelier): static
    {
        $this->date_atelier = $date_atelier;
        return $this;
    }

    public function getCapacite(): ?int { return $this->capacite; }
    public function setCapacite(?int $capacite): static
    {
        $this->capacite = $capacite;
        return $this;
    }

    public function getStatutAtelier(): ?int { return $this->statut_atelier; }
    public function setStatutAtelier(?int $statut_atelier): static
    {
        $this->statut_atelier = $statut_atelier;
        return $this;
    }

    public function getImageAtelier(): ?string { return $this->image_atelier; }
    public function setImageAtelier(?string $image_atelier): static
    {
        $this->image_atelier = $image_atelier;
        return $this;
    }

    public function getContexteAtelier(): ?int { return $this->contexte_atelier; }
    public function setContexteAtelier(?int $contexte_atelier): static
    {
        $this->contexte_atelier = $contexte_atelier;
        return $this;
    }

    public function getReservations(): Collection
    {
        return $this->reservations;
    }
}

