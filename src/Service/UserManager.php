<?php

namespace App\Service;

use App\Entity\User;

/**
 * =========================================================
 * UserManager — Service métier pour l'entité User
 * =========================================================
 *
 * Règles métier validées :
 *   R1 — Le nom (NOM) doit contenir entre 2 et 50 caractères.
 *   R2 — L'adresse e-mail doit être valide (format RFC 5321).
 *   R3 — Le rôle doit être une valeur autorisée (ROLE_USER, ROLE_ADMIN, ROLE_RECRUTEUR).
 *   R4 — Le numéro de téléphone, s'il est fourni, doit être positif.
 *
 * @author   Équipe Novas
 * @package  App\Service
 */
class UserManager
{
    // -------------------------------------------------------
    // Constantes métier
    // -------------------------------------------------------

    /** Longueur minimale du nom */
    public const NOM_MIN_LENGTH = 2;

    /** Longueur maximale du nom */
    public const NOM_MAX_LENGTH = 50;

    /** Rôles autorisés dans le système */
    public const ROLES_VALIDES = [
        'ROLE_USER',
        'ROLE_ADMIN',
        'ROLE_RECRUTEUR',
    ];

    // -------------------------------------------------------
    // Méthode principale de validation
    // -------------------------------------------------------

    /**
     * Valide toutes les règles métier d'un User.
     *
     * @param  User $user  L'entité à valider.
     *
     * @throws \InvalidArgumentException Si une règle métier est violée.
     *
     * @return true  Retourne true si toutes les règles sont respectées.
     */
    public function validate(User $user): bool
    {
        $this->validateNom($user->getNOM());
        $this->validateEmail($user->getEMAIL());
        $this->validateRole($user->getROLE());
        $this->validateNumero($user->getNUMERO());

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
    public function validateNom(?string $nom): void
    {
        if (empty($nom)) {
            throw new \InvalidArgumentException(
                'Le nom est obligatoire.'
            );
        }

        $len = mb_strlen(trim($nom));

        if ($len < self::NOM_MIN_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Le nom doit faire au moins %d caractères (reçu : %d).',
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
     * R2 — Valide l'adresse e-mail de l'utilisateur.
     *
     * @throws \InvalidArgumentException Si l'e-mail est vide ou invalide.
     */
    public function validateEmail(?string $email): void
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
     * R3 — Valide le rôle de l'utilisateur.
     *
     * @throws \InvalidArgumentException Si le rôle n'est pas dans la liste autorisée.
     */
    public function validateRole(?string $role): void
    {
        if (empty($role)) {
            throw new \InvalidArgumentException(
                'Le rôle est obligatoire.'
            );
        }

        if (!in_array($role, self::ROLES_VALIDES, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Le rôle "%s" n\'est pas valide. Valeurs autorisées : %s.',
                    $role,
                    implode(', ', self::ROLES_VALIDES)
                )
            );
        }
    }

    /**
     * R4 — Valide le numéro de téléphone (optionnel mais doit être positif si fourni).
     *
     * @throws \InvalidArgumentException Si le numéro est fourni mais est négatif ou nul.
     */
    public function validateNumero(?int $numero): void
    {
        if ($numero !== null && $numero <= 0) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Le numéro de téléphone doit être un entier positif — valeur reçue : %d.',
                    $numero
                )
            );
        }
    }
}
