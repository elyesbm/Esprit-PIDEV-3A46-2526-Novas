<?php

namespace App\Entity;

use App\Repository\HistoriqueVueRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HistoriqueVueRepository::class)]
#[ORM\Table(name: 'historique_vue')]
class HistoriqueVue
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_vue')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'ID', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Article::class)]
    #[ORM\JoinColumn(name: 'id_article', referencedColumnName: 'id_article', nullable: false, onDelete: 'CASCADE')]
    private ?Article $article = null;

    #[ORM\Column(name: 'date_vue', type: 'datetime_immutable')]
    private ?\DateTimeImmutable $dateVue = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getArticle(): ?Article
    {
        return $this->article;
    }

    public function setArticle(?Article $article): static
    {
        $this->article = $article;

        return $this;
    }

    public function getDateVue(): ?\DateTimeImmutable
    {
        return $this->dateVue;
    }

    public function setDateVue(\DateTimeImmutable $dateVue): static
    {
        $this->dateVue = $dateVue;

        return $this;
    }
}

