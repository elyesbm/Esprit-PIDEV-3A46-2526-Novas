<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;    
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Ignore;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id')]
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
    #[Ignore]
    private ?string $password = null;

    #[ORM\Column(name: 'actif', type: 'boolean', options: ['default' => true])]
    private bool $ACTIF = true;

    // 2FA - Two Factor Authentication
    #[ORM\Column(length: 255, nullable: true)]
    #[Ignore]
    private ?string $twoFactorSecret = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $twoFactorEnabledAt = null;

    /** @var list<string>|null */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $backupCodes = null;

    /** Encodage facial 128D (face-api.js) pour connexion par reconnaissance faciale */
    /** @var list<float>|null */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $faceEncoding = null;

    /** Token de réinitialisation du mot de passe */
    #[ORM\Column(length: 100, nullable: true, unique: true)]
    private ?string $resetToken = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $resetTokenExpiresAt = null;

    // 🔗 RELATIONS
    /** @var Collection<int, Article> */
    #[ORM\OneToMany(mappedBy: 'auteur', targetEntity: Article::class)]
    private Collection $articles;

    /** @var Collection<int, Commentaire> */
    #[ORM\OneToMany(mappedBy: 'auteur', targetEntity: Commentaire::class)]
    private Collection $commentaires;

    /** @var Collection<int, Publication> */
    #[ORM\OneToMany(mappedBy: 'auteur', targetEntity: Publication::class)]
    private Collection $publications;

    /** @var Collection<int, Offrejob> */
    #[ORM\OneToMany(mappedBy: 'createur', targetEntity: Offrejob::class)]
    private Collection $offrejobs;

    /** @var Collection<int, CondidatureJob> */
    #[ORM\OneToMany(mappedBy: 'condidat', targetEntity: CondidatureJob::class)]
    private Collection $condidatureJobs;

    /** @var Collection<int, CandidatureJob> */
    #[ORM\OneToMany(mappedBy: 'candidat', targetEntity: CandidatureJob::class)]
    private Collection $candidatureJobs;

    /** @var Collection<int, Reservation> */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Reservation::class)]
    private Collection $reservations;

    /** @var Collection<int, Skill> */
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
    public function setPassword(#[\SensitiveParameter] string $password): static { $this->password = $password; return $this; }

    public function getACTIF(): bool { return $this->ACTIF; }
    public function setACTIF(bool $ACTIF): static { $this->ACTIF = $ACTIF; return $this; }

    // 2FA Getters and Setters
    public function getTwoFactorSecret(): ?string { return $this->twoFactorSecret; }
    public function setTwoFactorSecret(#[\SensitiveParameter] ?string $twoFactorSecret): static { $this->twoFactorSecret = $twoFactorSecret; return $this; }

    public function getTwoFactorEnabledAt(): ?\DateTimeInterface { return $this->twoFactorEnabledAt; }
    public function setTwoFactorEnabledAt(?\DateTimeInterface $twoFactorEnabledAt): static { $this->twoFactorEnabledAt = $twoFactorEnabledAt; return $this; }

    public function isTwoFactorEnabled(): bool { return $this->twoFactorEnabledAt !== null; }
    public function enableTwoFactor(): static { $this->twoFactorEnabledAt = new \DateTime('now'); return $this; }
    public function disableTwoFactor(): static { $this->twoFactorEnabledAt = null; return $this; }

    /** @return list<string>|null */
    public function getBackupCodes(): ?array
    {
        if ($this->backupCodes === null) {
            return null;
        }

        $codes = [];
        foreach ($this->backupCodes as $code) {
            if (\is_string($code) && $code !== '') {
                $codes[] = $code;
            }
        }

        return $codes;
    }
    /** @param list<string>|null $backupCodes */
    public function setBackupCodes(?array $backupCodes): static
    {
        if ($backupCodes === null) {
            $this->backupCodes = null;
            return $this;
        }

        $codes = [];
        foreach (array_values($backupCodes) as $code) {
            if (\is_string($code) && $code !== '') {
                $codes[] = $code;
            }
        }
        $this->backupCodes = $codes;
        return $this;
    }

    /** @return list<float>|null */
    public function getFaceEncoding(): ?array
    {
        if ($this->faceEncoding === null) {
            return null;
        }

        $encoding = [];
        foreach ($this->faceEncoding as $value) {
            if (\is_int($value) || \is_float($value)) {
                $encoding[] = (float) $value;
            }
        }

        return $encoding;
    }
    /** @param list<float>|null $faceEncoding */
    public function setFaceEncoding(?array $faceEncoding): static
    {
        if ($faceEncoding === null) {
            $this->faceEncoding = null;
            return $this;
        }

        $encoding = [];
        foreach (array_values($faceEncoding) as $value) {
            if (\is_int($value) || \is_float($value)) {
                $encoding[] = (float) $value;
            }
        }
        $this->faceEncoding = $encoding;
        return $this;
    }
    public function hasFaceEncoding(): bool { return $this->faceEncoding !== null && \count($this->faceEncoding) > 0; }

    public function getResetToken(): ?string { return $this->resetToken; }
    public function setResetToken(?string $resetToken): static { $this->resetToken = $resetToken; return $this; }
    public function getResetTokenExpiresAt(): ?\DateTimeInterface { return $this->resetTokenExpiresAt; }
    public function setResetTokenExpiresAt(?\DateTimeInterface $dt): static { $this->resetTokenExpiresAt = $dt; return $this; }
    public function isResetTokenValid(): bool
    {
        return $this->resetToken !== null && $this->resetTokenExpiresAt !== null && $this->resetTokenExpiresAt > new \DateTime();
    }

    /** @return list<string> */
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
    /** @return Collection<int, Article> */
    public function getArticles(): Collection { return $this->articles; }
    /** @return Collection<int, Commentaire> */
    public function getCommentaires(): Collection { return $this->commentaires; }
    /** @return Collection<int, Publication> */
    public function getPublications(): Collection { return $this->publications; }
    /** @return Collection<int, Offrejob> */
    public function getOffrejobs(): Collection { return $this->offrejobs; }
    /** @return Collection<int, CondidatureJob> */
    public function getCondidatureJobs(): Collection { return $this->condidatureJobs; }
    /** @return Collection<int, CandidatureJob> */
    public function getCandidatureJobs(): Collection { return $this->candidatureJobs; }
    /** @return Collection<int, Reservation> */
    public function getReservations(): Collection { return $this->reservations; }
    /** @return Collection<int, Skill> */
    public function getSkills(): Collection { return $this->skills; }
}
