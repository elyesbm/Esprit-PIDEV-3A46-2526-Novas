<?php

namespace App\Entity;

use App\Repository\CommentaireRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CommentaireRepository::class)]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: false)]
#[Gedmo\Tree(type: 'materializedPath')]
class Commentaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_comm')]
    #[Gedmo\TreePathSource]
    private ?int $id = null;

    #[ORM\Column(type: 'text')]
    private ?string $image = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $date_creation = null;

    #[ORM\Column(type: 'text')]
    #[Assert\Length(
        min: 0,
        max: 1000,
        maxMessage: "Le commentaire ne doit pas dépasser {{ limit }} caractères."
    )]
    private ?string $contenu = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'commentaires')]
    #[ORM\JoinColumn(name: 'id_auteur', referencedColumnName: 'ID', nullable: false)]
    private ?User $auteur = null;

    #[ORM\ManyToOne(targetEntity: Publication::class, inversedBy: 'commentaires')]
    #[ORM\JoinColumn(name: 'id_pub', referencedColumnName: 'id_pub', nullable: false)]
    private ?Publication $publication = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'id_parent', referencedColumnName: 'id_comm', nullable: true, onDelete: 'CASCADE')]
    #[Gedmo\TreeParent]
    private ?self $parent = null;

    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class, cascade: ['remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['date_creation' => 'ASC'])]
    private \Doctrine\Common\Collections\Collection $children;

    /** Niveau dans l'arbre (0 = racine). Rempli automatiquement par Gedmo Tree. */
    #[ORM\Column(name: 'tree_level', type: 'integer', nullable: true)]
    #[Gedmo\TreeLevel]
    private ?int $level = null;

    /** Chemin matérialisé (ex: /1/5/12). Rempli automatiquement par Gedmo Tree. */
    #[ORM\Column(name: 'tree_path', type: 'string', length: 500, nullable: true)]
    #[Gedmo\TreePath(separator: '/', appendId: true)]
    private ?string $path = null;

    #[ORM\Column(name: 'deleted_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $deletedAt = null;

    /** Nom du fichier audio (enregistrement vocal) dans uploads/comment_audio/ */
    #[ORM\Column(name: 'audio_filename', type: 'string', length: 255, nullable: true)]
    private ?string $audioFilename = null;

    public function __construct()
    {
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;
        return $this;
    }

    /** @return \Doctrine\Common\Collections\Collection<int, Commentaire> */
    public function getChildren(): \Doctrine\Common\Collections\Collection
    {
        return $this->children;
    }

    public function getLevel(): ?int
    {
        return $this->level;
    }

    public function setLevel(?int $level): static
    {
        $this->level = $level;
        return $this;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(?string $path): static
    {
        $this->path = $path;
        return $this;
    }

    public function getId(): ?int { return $this->id; }
    public function getImage(): ?string { return $this->image; }
    public function setImage(string $image): static { $this->image = $image; return $this; }
    public function getDateCreation(): ?\DateTimeInterface { return $this->date_creation; }
    public function setDateCreation(\DateTimeInterface $date_creation): static { $this->date_creation = $date_creation; return $this; }
    public function getContenu(): ?string { return $this->contenu; }
    public function setContenu(?string $contenu): static { $this->contenu = $contenu; return $this; }
    public function getAuteur(): ?User { return $this->auteur; }
    public function setAuteur(?User $auteur): static { $this->auteur = $auteur; return $this; }
    public function getPublication(): ?Publication { return $this->publication; }
    public function setPublication(?Publication $publication): static { $this->publication = $publication; return $this; }
    public function getDeletedAt(): ?\DateTimeInterface { return $this->deletedAt; }
    public function setDeletedAt(?\DateTimeInterface $deletedAt): static { $this->deletedAt = $deletedAt; return $this; }
    public function getAudioFilename(): ?string { return $this->audioFilename; }
    public function setAudioFilename(?string $audioFilename): static { $this->audioFilename = $audioFilename; return $this; }
}
