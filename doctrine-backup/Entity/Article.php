<?php

namespace App\Entity;

use App\Repository\ArticleRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArticleRepository::class)]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_article')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $titre_article = null;

    #[ORM\Column(length: 255)]
    private ?string $contenue_article = null;

    #[ORM\Column(type: 'text')]
    private ?string $image_article = null;

    #[ORM\Column(length: 50)]
    private ?string $type_article = null;

    #[ORM\Column]
    private ?float $prix_article = null;

    #[ORM\Column(length: 50)]
    private ?string $statut_article = null;

    #[ORM\Column(options: ['default' => 0])]
    private ?int $vues_article = 0;

    // 🔗 CLÉS ÉTRANGÈRES
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'articles')]
    #[ORM\JoinColumn(name: 'id_etudiant', referencedColumnName: 'ID', nullable: false, onDelete: 'CASCADE')]
    private ?User $auteur = null;

    #[ORM\ManyToOne(targetEntity: Categorie::class, inversedBy: 'articles')]
    #[ORM\JoinColumn(name: 'id_categorie', referencedColumnName: 'id_categorie', nullable: false, onDelete: 'CASCADE')]
    private ?Categorie $categorie = null;

    public function getId(): ?int { return $this->id; }
    public function getTitreArticle(): ?string { return $this->titre_article; }
    public function setTitreArticle(string $titre_article): static { $this->titre_article = $titre_article; return $this; }
    public function getContenueArticle(): ?string { return $this->contenue_article; }
    public function setContenueArticle(string $contenue_article): static { $this->contenue_article = $contenue_article; return $this; }
    public function getImageArticle(): ?string { return $this->image_article; }
    public function setImageArticle(string $image_article): static { $this->image_article = $image_article; return $this; }
    public function getTypeArticle(): ?string { return $this->type_article; }
    public function setTypeArticle(string $type_article): static { $this->type_article = $type_article; return $this; }
    public function getPrixArticle(): ?float { return $this->prix_article; }
    public function setPrixArticle(float $prix_article): static { $this->prix_article = $prix_article; return $this; }
    public function getStatutArticle(): ?string { return $this->statut_article; }
    public function setStatutArticle(string $statut_article): static { $this->statut_article = $statut_article; return $this; }
    public function getVuesArticle(): ?int { return $this->vues_article; }
    public function setVuesArticle(int $vues_article): static { $this->vues_article = max(0, $vues_article); return $this; }
    public function getAuteur(): ?User { return $this->auteur; }
    public function setAuteur(?User $auteur): static { $this->auteur = $auteur; return $this; }
    public function getCategorie(): ?Categorie { return $this->categorie; }
    public function setCategorie(?Categorie $categorie): static { $this->categorie = $categorie; return $this; }
}
