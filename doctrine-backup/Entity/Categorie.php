<?php

namespace App\Entity;

use App\Repository\CategorieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategorieRepository::class)]
class Categorie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_categorie')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom_categorie = null;

    #[ORM\Column(length: 255)]
    private ?string $description_categorie = null;

    #[ORM\OneToMany(mappedBy: 'categorie', targetEntity: Article::class)]
    private Collection $articles;

    public function __construct()
    {
        $this->articles = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getNomCategorie(): ?string { return $this->nom_categorie; }
    public function setNomCategorie(string $nom_categorie): static { $this->nom_categorie = $nom_categorie; return $this; }
    public function getDescriptionCategorie(): ?string { return $this->description_categorie; }
    public function setDescriptionCategorie(string $description_categorie): static { $this->description_categorie = $description_categorie; return $this; }
    public function getArticles(): Collection { return $this->articles; }
}