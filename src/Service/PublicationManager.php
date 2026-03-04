<?php

namespace App\Service;

use App\Entity\Publication;

/**
 * =========================================================
 * PublicationManager — Service métier pour l'entité Publication
 * =========================================================
 *
 * Règles métier validées :
 *   R1 — Le titre doit contenir entre 3 et 150 caractères.
 *   R2 — Le contenu doit contenir au moins 5 caractères.
 *   R3 — Le contexte doit valoir 1 (Partage) ou 2 (Demande d'aide).
 *   R4 — La date de création ne peut pas être dans le futur.
 *
 * @author   Équipe Novas
 * @package  App\Service
 */
class PublicationManager
{
    // -------------------------------------------------------
    // Constantes métier
    // -------------------------------------------------------

    /** Longueur minimale du titre */
    public const TITRE_MIN_LENGTH = 3;

    /** Longueur maximale du titre */
    public const TITRE_MAX_LENGTH = 150;

    /** Longueur minimale du contenu */
    public const CONTENU_MIN_LENGTH = 5;

    /** Valeurs autorisées pour le contexte */
    public const CONTEXTES_VALIDES = [1, 2];

    // -------------------------------------------------------
    // Méthode principale de validation
    // -------------------------------------------------------

    /**
     * Valide toutes les règles métier d'une Publication.
     *
     * @param  Publication $publication  L'entité à valider.
     *
     * @throws \InvalidArgumentException Si une règle métier est violée.
     *
     * @return true  Retourne true si toutes les règles sont respectées.
     */
    public function validate(Publication $publication): bool
    {
        $this->validateTitre($publication->getTitre());
        $this->validateContenu($publication->getContenu());
        $this->validateContexte($publication->getContexte());
        $this->validateDateCreation($publication->getDateCreation());

        return true;
    }

    // -------------------------------------------------------
    // Validations individuelles (appelables séparément)
    // -------------------------------------------------------

    /**
     * R1 — Valide le titre.
     *
     * @throws \InvalidArgumentException Si le titre est vide ou hors limites.
     */
    public function validateTitre(?string $titre): void
    {
        if (empty($titre)) {
            throw new \InvalidArgumentException(
                'Le titre est obligatoire.'
            );
        }

        $len = mb_strlen(trim($titre));

        if ($len < self::TITRE_MIN_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Le titre doit contenir au moins %d caractères (reçu : %d).',
                    self::TITRE_MIN_LENGTH,
                    $len
                )
            );
        }

        if ($len > self::TITRE_MAX_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Le titre ne doit pas dépasser %d caractères (reçu : %d).',
                    self::TITRE_MAX_LENGTH,
                    $len
                )
            );
        }
    }

    /**
     * R2 — Valide le contenu.
     *
     * @throws \InvalidArgumentException Si le contenu est vide ou trop court.
     */
    public function validateContenu(?string $contenu): void
    {
        if (empty($contenu)) {
            throw new \InvalidArgumentException(
                'Le contenu est obligatoire.'
            );
        }

        $len = mb_strlen(trim($contenu));

        if ($len < self::CONTENU_MIN_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Le contenu doit contenir au moins %d caractères (reçu : %d).',
                    self::CONTENU_MIN_LENGTH,
                    $len
                )
            );
        }
    }

    /**
     * R3 — Valide le contexte (1 = Partage, 2 = Demande d'aide).
     *
     * @throws \InvalidArgumentException Si le contexte n'est pas dans les valeurs autorisées.
     */
    public function validateContexte(?int $contexte): void
    {
        if ($contexte === null || !in_array($contexte, self::CONTEXTES_VALIDES, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Le contexte doit être 1 (Partage) ou 2 (Demande d\'aide) — valeur reçue : %s.',
                    var_export($contexte, true)
                )
            );
        }
    }

    /**
     * R4 — Valide que la date de création n'est pas dans le futur.
     *
     * @throws \InvalidArgumentException Si la date est dans le futur.
     */
    public function validateDateCreation(?\DateTimeInterface $date): void
    {
        if ($date === null) {
            throw new \InvalidArgumentException(
                'La date de création est obligatoire.'
            );
        }

        if ($date > new \DateTime()) {
            throw new \InvalidArgumentException(
                'La date de création ne peut pas être dans le futur.'
            );
        }
    }
}
