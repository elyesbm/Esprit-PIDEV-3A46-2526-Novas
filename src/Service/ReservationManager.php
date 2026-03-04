<?php

namespace App\Service;

use App\Entity\Reservation;

/**
 * =========================================================
 * ReservationManager — Service métier pour l'entité Reservation
 * =========================================================
 *
 * Règles métier validées :
 *   R1 — Le nom de l'utilisateur doit contenir entre 2 et 255 caractères.
 *   R2 — L'adresse e-mail doit être valide (format RFC 5321).
 *   R3 — Le statut de la réservation doit être 0 (en attente), 1 (confirmé) ou 2 (annulé).
 *   R4 — L'atelier et l'utilisateur associés sont obligatoires.
 *
 * @author   Équipe Novas
 * @package  App\Service
 */
class ReservationManager
{
    // -------------------------------------------------------
    // Constantes métier
    // -------------------------------------------------------

    /** Longueur minimale du nom */
    public const NOM_MIN_LENGTH = 2;

    /** Longueur maximale du nom */
    public const NOM_MAX_LENGTH = 255;

    /** Statuts autorisés : 0 = en attente, 1 = confirmé, 2 = annulé */
    public const STATUTS_VALIDES = [0, 1, 2];

    // -------------------------------------------------------
    // Méthode principale de validation
    // -------------------------------------------------------

    /**
     * Valide toutes les règles métier d'une Reservation.
     *
     * @param  Reservation $reservation  L'entité à valider.
     *
     * @throws \InvalidArgumentException Si une règle métier est violée.
     *
     * @return true  Retourne true si toutes les règles sont respectées.
     */
    public function validate(Reservation $reservation): bool
    {
        $this->validateNomUser($reservation->getNomUser());
        $this->validateEmailUser($reservation->getEmailUser());
        $this->validateStatut($reservation->getStatutReservation());
        $this->validateRelations($reservation);

        return true;
    }

    // -------------------------------------------------------
    // Validations individuelles
    // -------------------------------------------------------

    /**
     * R1 — Valide le nom de l'utilisateur.
     *
     * @throws \InvalidArgumentException Si le nom est vide ou hors limites.
     */
    public function validateNomUser(?string $nom): void
    {
        if (empty($nom)) {
            throw new \InvalidArgumentException(
                'Le nom de l\'utilisateur est obligatoire.'
            );
        }

        $len = mb_strlen(trim($nom));

        if ($len < self::NOM_MIN_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Le nom doit contenir au moins %d caractères (reçu : %d).',
                    self::NOM_MIN_LENGTH,
                    $len
                )
            );
        }

        if ($len > self::NOM_MAX_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Le nom ne peut pas dépasser %d caractères (reçu : %d).',
                    self::NOM_MAX_LENGTH,
                    $len
                )
            );
        }
    }

    /**
     * R2 — Valide l'adresse e-mail.
     *
     * @throws \InvalidArgumentException Si l'e-mail est vide ou invalide.
     */
    public function validateEmailUser(?string $email): void
    {
        if (empty($email)) {
            throw new \InvalidArgumentException(
                'L\'adresse e-mail est obligatoire.'
            );
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'L\'adresse e-mail "%s" n\'est pas valide.',
                    $email
                )
            );
        }
    }

    /**
     * R3 — Valide le statut de la réservation.
     *
     * @throws \InvalidArgumentException Si le statut n'est pas valide.
     */
    public function validateStatut(int $statut): void
    {
        if (!in_array($statut, self::STATUTS_VALIDES, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Le statut doit être 0 (en attente), 1 (confirmé) ou 2 (annulé) — valeur reçue : %d.',
                    $statut
                )
            );
        }
    }

    /**
     * R4 — Valide que l'atelier et l'utilisateur sont renseignés.
     *
     * @throws \InvalidArgumentException Si l'atelier ou l'utilisateur est absent.
     */
    public function validateRelations(Reservation $reservation): void
    {
        if ($reservation->getUser() === null) {
            throw new \InvalidArgumentException(
                'L\'utilisateur associé à la réservation est obligatoire.'
            );
        }

        if ($reservation->getAtelier() === null) {
            throw new \InvalidArgumentException(
                'L\'atelier associé à la réservation est obligatoire.'
            );
        }
    }
}
