<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;    
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert; // ⬅️ AJOUTE CECI

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom est obligatoire")] // ⬅️ CONTRAINTE
    #[Assert\Length(min: 2, max: 50, minMessage: "Le nom doit faire au moins {{ limit }} caractères")]
    private ?string $NOM = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le prénom est obligatoire")] // ⬅️ CONTRAINTE
    #[Assert\Length(min: 2, max: 50, minMessage: "Le prénom doit faire au moins {{ limit }} caractères")]
    private ?string $PRENOM = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "L'email est obligatoire")] // ⬅️ CONTRAINTE
    #[Assert\Email(message: "L'email '{{ value }}' n'est pas valide")] // ⬅️ CONTRAINTE
    private ?string $EMAIL = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $IMAGE = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Positive(message: "Le numéro doit être positif")] // ⬅️ CONTRAINTE (optionnel)
    private ?int $NUMERO = null;

    #[ORM\Column(length: 255)]
    private ?string $ROLE = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(name: 'ACTIF', type: 'boolean', options: ['default' => true])]
    private bool $ACTIF = true;

    // 2FA - Two Factor Authentication
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $twoFactorSecret = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $twoFactorEnabledAt = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $backupCodes = null;

    /** Encodage facial 128D (face-api.js) pour connexion par reconnaissance faciale */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $faceEncoding = null;

    /** Token de réinitialisation du mot de passe */
    #[ORM\Column(length: 100, nullable: true, unique: true)]
    private ?string $resetToken = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $resetTokenExpiresAt = null;

    // 🔗 RELATIONS
    #[ORM\OneToMany(mappedBy: 'auteur', targetEntity: Article::class)]
    private Collection $articles;

    #[ORM\OneToMany(mappedBy: 'auteur', targetEntity: Commentaire::class)]
    private Collection $commentaires;

    #[ORM\OneToMany(mappedBy: 'auteur', targetEntity: Publication::class)]
    private Collection $publications;

    #[ORM\OneToMany(mappedBy: 'createur', targetEntity: Offrejob::class)]
    private Collection $offrejobs;

    #[ORM\OneToMany(mappedBy: 'condidat', targetEntity: CondidatureJob::class)]
    private Collection $condidatureJobs;

    #[ORM\OneToMany(mappedBy: 'candidat', targetEntity: CandidatureJob::class)]
    private Collection $candidatureJobs;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Reservation::class)]
    private Collection $reservations;

    #[ORM\OneToMany(mappedBy: 'createur', targetEntity: Skill::class)]
    private Collection $skills;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: CvProfile::class, cascade: ['persist', 'remove'])]
    private ?CvProfile $cvProfile = null;

    public function __construct()
    {
        $this->articles = new ArrayCollection();
        $this->commentaires = new ArrayCollection();
        $this->publications = new ArrayCollection();
        $this->offrejobs = new ArrayCollection();
        $this->condidatureJobs = new ArrayCollection();
        $this->candidatureJobs = new ArrayCollection();
        $this->reservations = new ArrayCollection();
        $this->skills = new ArrayCollection();
    }

    public function getCvProfile(): ?CvProfile { return $this->cvProfile; }
    public function setCvProfile(?CvProfile $cvProfile): static { $this->cvProfile = $cvProfile; return $this; }

    // Getters et setters basiques...
    public function getId(): ?int { return $this->id; }
    public function getNOM(): ?string { return $this->NOM; }
    public function setNOM(string $NOM): static { $this->NOM = $NOM; return $this; }
    public function getPRENOM(): ?string { return $this->PRENOM; }
    public function setPRENOM(string $PRENOM): static { $this->PRENOM = $PRENOM; return $this; }
    public function getEMAIL(): ?string { return $this->EMAIL; }
    public function setEMAIL(string $EMAIL): static { $this->EMAIL = $EMAIL; return $this; }
    public function getIMAGE(): ?string { return $this->IMAGE; }
    public function setIMAGE(?string $IMAGE): static { $this->IMAGE = $IMAGE; return $this; }
    public function getNUMERO(): ?int { return $this->NUMERO; }
    public function setNUMERO(?int $NUMERO): static { $this->NUMERO = $NUMERO; return $this; }
    public function getROLE(): ?string { return $this->ROLE; }
    public function setROLE(string $ROLE): static { $this->ROLE = $ROLE; return $this; }

    public function getPassword(): ?string { return $this->password; }
    public function setPassword(string $password): static { $this->password = $password; return $this; }

    public function getACTIF(): bool { return $this->ACTIF; }
    public function setACTIF(bool $ACTIF): static { $this->ACTIF = $ACTIF; return $this; }

    // 2FA Getters and Setters
    public function getTwoFactorSecret(): ?string { return $this->twoFactorSecret; }
    public function setTwoFactorSecret(?string $twoFactorSecret): static { $this->twoFactorSecret = $twoFactorSecret; return $this; }

    public function getTwoFactorEnabledAt(): ?\DateTimeInterface { return $this->twoFactorEnabledAt; }
    public function setTwoFactorEnabledAt(?\DateTimeInterface $twoFactorEnabledAt): static { $this->twoFactorEnabledAt = $twoFactorEnabledAt; return $this; }

    public function isTwoFactorEnabled(): bool { return $this->twoFactorEnabledAt !== null; }
    public function enableTwoFactor(): static { $this->twoFactorEnabledAt = new \DateTime('now'); return $this; }
    public function disableTwoFactor(): static { $this->twoFactorEnabledAt = null; return $this; }

    public function getBackupCodes(): ?array { return $this->backupCodes; }
    public function setBackupCodes(?array $backupCodes): static { $this->backupCodes = $backupCodes; return $this; }

    public function getFaceEncoding(): ?array { return $this->faceEncoding; }
    public function setFaceEncoding(?array $faceEncoding): static { $this->faceEncoding = $faceEncoding; return $this; }
    public function hasFaceEncoding(): bool { return $this->faceEncoding !== null && \count($this->faceEncoding) > 0; }

    public function getResetToken(): ?string { return $this->resetToken; }
    public function setResetToken(?string $resetToken): static { $this->resetToken = $resetToken; return $this; }
    public function getResetTokenExpiresAt(): ?\DateTimeInterface { return $this->resetTokenExpiresAt; }
    public function setResetTokenExpiresAt(?\DateTimeInterface $dt): static { $this->resetTokenExpiresAt = $dt; return $this; }
    public function isResetTokenValid(): bool
    {
        return $this->resetToken !== null && $this->resetTokenExpiresAt !== null && $this->resetTokenExpiresAt > new \DateTime();
    }

    public function getRoles(): array
    {
        $role = $this->ROLE ?? 'ROLE_USER';
        return [$role];
    }

    public function eraseCredentials(): void
    {
        // rien à effacer si on ne stocke jamais le mot de passe en clair
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->EMAIL;
    }

    // Getters pour les collections
    public function getArticles(): Collection { return $this->articles; }
    public function getCommentaires(): Collection { return $this->commentaires; }
    public function getPublications(): Collection { return $this->publications; }
    public function getOffrejobs(): Collection { return $this->offrejobs; }
    public function getCondidatureJobs(): Collection { return $this->condidatureJobs; }
    public function getCandidatureJobs(): Collection { return $this->candidatureJobs; }
    public function getReservations(): Collection { return $this->reservations; }
    public function getSkills(): Collection { return $this->skills; }
}
