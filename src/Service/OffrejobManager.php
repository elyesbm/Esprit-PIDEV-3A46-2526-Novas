<?php

namespace App\Service;

use App\Entity\Offrejob;
use App\Enum\OffreStatut;

/**
 * =========================================================
 * OffrejobManager — Service métier pour l'entité Offrejob
 * =========================================================
 *
 * Règles métier validées :
 *   R1 — Le titre de l'offre est obligatoire (non vide).
 *   R2 — La capacité maximale doit être strictement positive (> 0).
 *   R3 — La capacité restante ne doit pas être négative.
 *   R4 — La capacité restante ne peut pas dépasser la capacité maximale.
 *   R5 — La date d'expiration doit être dans le futur.
 *
 * @author   Équipe Novas
 * @package  App\Service
 */
class OffrejobManager
{
    // -------------------------------------------------------
    // Constantes métier
    // -------------------------------------------------------

    /** Longueur minimale du titre de l'offre */
    public const TITRE_MIN_LENGTH = 3;

    /** Capacité minimale (strictement positive) */
    public const CAPACITE_MIN = 1;

    // -------------------------------------------------------
    // Méthode principale de validation
    // -------------------------------------------------------

    /**
     * Valide toutes les règles métier d'une Offrejob.
     *
     * @param  Offrejob $offre  L'entité à valider.
     *
     * @throws \InvalidArgumentException Si une règle métier est violée.
     *
     * @return true  Retourne true si toutes les règles sont respectées.
     */
    public function validate(Offrejob $offre): bool
    {
        $this->validateTitre($offre->getTitreOffre());
        $this->validateCapaciteMax($offre->getCapaciteMax());
        $this->validateCapaciteRestante($offre->getCapaciteRestante());
        $this->validateCoherenceCapacite($offre->getCapaciteRestante(), $offre->getCapaciteMax());
        $this->validateDateExpiration($offre->getDateExpiration());

        return true;
    }

    // -------------------------------------------------------
    // Validations individuelles
    // -------------------------------------------------------

    /**
     * R1 — Valide le titre de l'offre.
     *
     * @throws \InvalidArgumentException Si le titre est vide ou trop court.
     */
    public function validateTitre(?string $titre): void
    {
        if (empty($titre) || trim($titre) === '') {
            throw new \InvalidArgumentException(
                'Le titre de l\'offre est obligatoire.'
            );
        }

        $len = mb_strlen(trim($titre));

        if ($len < self::TITRE_MIN_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Le titre de l\'offre doit contenir au moins %d caractères (reçu : %d).',
                    self::TITRE_MIN_LENGTH,
                    $len
                )
            );
        }
    }

    /**
     * R2 — Valide que la capacité maximale est strictement positive.
     *
     * @throws \InvalidArgumentException Si la capacité maximale est ≤ 0.
     */
    public function validateCapaciteMax(int $capaciteMax): void
    {
        if ($capaciteMax < self::CAPACITE_MIN) {
            throw new \InvalidArgumentException(
                sprintf(
                    'La capacité maximale doit être supérieure à 0 — valeur reçue : %d.',
                    $capaciteMax
                )
            );
        }
    }

    /**
     * R3 — Valide que la capacité restante n'est pas négative.
     *
     * @throws \InvalidArgumentException Si la capacité restante est négative.
     */
    public function validateCapaciteRestante(int $capaciteRestante): void
    {
        if ($capaciteRestante < 0) {
            throw new \InvalidArgumentException(
                sprintf(
                    'La capacité restante ne peut pas être négative — valeur reçue : %d.',
                    $capaciteRestante
                )
            );
        }
    }

    /**
     * R4 — Valide que la capacité restante ne dépasse pas la capacité maximale.
     *
     * @throws \InvalidArgumentException Si la capacité restante > capacité maximale.
     */
    public function validateCoherenceCapacite(int $restante, int $max): void
    {
        if ($restante > $max) {
            throw new \InvalidArgumentException(
                sprintf(
                    'La capacité restante (%d) ne peut pas dépasser la capacité maximale (%d).',
                    $restante,
                    $max
                )
            );
        }
    }

    /**
     * R5 — Valide que la date d'expiration est dans le futur.
     *
     * @throws \InvalidArgumentException Si la date est passée ou absente.
     */
    public function validateDateExpiration(?\DateTimeImmutable $dateExpiration): void
    {
        if ($dateExpiration === null) {
            throw new \InvalidArgumentException(
                'La date d\'expiration est obligatoire.'
            );
        }

        if ($dateExpiration <= new \DateTimeImmutable()) {
            throw new \InvalidArgumentException(
                sprintf(
                    'La date d\'expiration doit être dans le futur — date reçue : %s.',
                    $dateExpiration->format('d/m/Y H:i:s')
                )
            );
        }
    }
}
