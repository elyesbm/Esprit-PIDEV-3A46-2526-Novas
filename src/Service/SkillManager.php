<?php

namespace App\Service;

use App\Entity\Skill;

/**
 * =========================================================
 * SkillManager — Service métier pour l'entité Skill
 * =========================================================
 *
 * Règles métier validées :
 *   R1 — Le nom du skill doit contenir entre 3 et 255 caractères.
 *   R2 — La catégorie est obligatoire (non vide).
 *   R3 — Le contexte du skill est obligatoire (non vide).
 *   R4 — Le score de demande marché, s'il est fourni, doit être compris entre 1 et 5.
 *
 * @author   Équipe Novas
 * @package  App\Service
 */
class SkillManager
{
    // -------------------------------------------------------
    // Constantes métier
    // -------------------------------------------------------

    /** Longueur minimale du nom du skill */
    public const NOM_MIN_LENGTH = 3;

    /** Longueur maximale du nom du skill */
    public const NOM_MAX_LENGTH = 255;

    /** Score de demande minimum sur le marché */
    public const SCORE_MIN = 1;

    /** Score de demande maximum sur le marché */
    public const SCORE_MAX = 5;

    // -------------------------------------------------------
    // Méthode principale de validation
    // -------------------------------------------------------

    /**
     * Valide toutes les règles métier d'un Skill.
     *
     * @param  Skill $skill  L'entité à valider.
     *
     * @throws \InvalidArgumentException Si une règle métier est violée.
     *
     * @return true  Retourne true si toutes les règles sont respectées.
     */
    public function validate(Skill $skill): bool
    {
        $this->validateNomSkill($skill->getNomSkill());
        $this->validateCategorie($skill->getCategorie());
        $this->validateContexteSkill($skill->getContexteSkill());
        $this->validateScoreDemande($skill->getScoreDemande());

        return true;
    }

    // -------------------------------------------------------
    // Validations individuelles
    // -------------------------------------------------------

    /**
     * R1 — Valide le nom du skill.
     *
     * @throws \InvalidArgumentException Si le nom est vide ou hors limites.
     */
    public function validateNomSkill(?string $nom): void
    {
        if (empty($nom)) {
            throw new \InvalidArgumentException(
                'Le nom du skill est obligatoire.'
            );
        }

        $len = mb_strlen(trim($nom));

        if ($len < self::NOM_MIN_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Le nom du skill doit contenir au moins %d caractères (reçu : %d).',
                    self::NOM_MIN_LENGTH,
                    $len
                )
            );
        }

        if ($len > self::NOM_MAX_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Le nom du skill ne peut pas dépasser %d caractères (reçu : %d).',
                    self::NOM_MAX_LENGTH,
                    $len
                )
            );
        }
    }

    /**
     * R2 — Valide la catégorie du skill.
     *
     * @throws \InvalidArgumentException Si la catégorie est vide.
     */
    public function validateCategorie(?string $categorie): void
    {
        if (empty($categorie) || trim($categorie) === '') {
            throw new \InvalidArgumentException(
                'La catégorie du skill est obligatoire.'
            );
        }
    }

    /**
     * R3 — Valide le contexte du skill.
     *
     * @throws \InvalidArgumentException Si le contexte est vide.
     */
    public function validateContexteSkill(?string $contexte): void
    {
        if (empty($contexte) || trim($contexte) === '') {
            throw new \InvalidArgumentException(
                'Le contexte (type de compétence) du skill est obligatoire.'
            );
        }
    }

    /**
     * R4 — Valide le score de demande marché (1 à 5, optionnel).
     *
     * @throws \InvalidArgumentException Si le score est hors de la plage [1, 5].
     */
    public function validateScoreDemande(?int $score): void
    {
        if ($score === null) {
            // Champ optionnel — aucune validation nécessaire.
            return;
        }

        if ($score < self::SCORE_MIN || $score > self::SCORE_MAX) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Le score de demande doit être compris entre %d et %d — valeur reçue : %d.',
                    self::SCORE_MIN,
                    self::SCORE_MAX,
                    $score
                )
            );
        }
    }
}
