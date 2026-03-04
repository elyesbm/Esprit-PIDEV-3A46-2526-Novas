<?php

namespace App\Service;

use App\Entity\Commande;

/**
 * =========================================================
 * CommandeManager — Service métier pour l'entité Commande
 * =========================================================
 *
 * Règles métier validées :
 *   R1 — Le montant doit être strictement positif (> 0).
 *   R2 — L'ID de session Stripe est obligatoire et ne peut pas être vide.
 *   R3 — La date de commande ne peut pas être dans le futur.
 *   R4 — L'article associé est obligatoire.
 *
 * @author   Équipe Novas
 * @package  App\Service
 */
class CommandeManager
{
    // -------------------------------------------------------
    // Constantes métier
    // -------------------------------------------------------

    /** Montant minimum accepté (en euros) */
    public const MONTANT_MIN = 0.01;

    /** Longueur minimale de l'identifiant de session Stripe */
    public const STRIPE_SESSION_MIN_LENGTH = 10;

    // -------------------------------------------------------
    // Méthode principale de validation
    // -------------------------------------------------------

    /**
     * Valide toutes les règles métier d'une Commande.
     *
     * @param  Commande $commande  L'entité à valider.
     *
     * @throws \InvalidArgumentException Si une règle métier est violée.
     *
     * @return true  Retourne true si toutes les règles sont respectées.
     */
    public function validate(Commande $commande): bool
    {
        $this->validateMontant($commande->getMontant());
        $this->validateStripeSessionId($commande->getStripeSessionId());
        $this->validateDateCommande($commande->getDateCommande());
        $this->validateArticle($commande);

        return true;
    }

    // -------------------------------------------------------
    // Validations individuelles
    // -------------------------------------------------------

    /**
     * R1 — Valide que le montant est strictement positif.
     *
     * @throws \InvalidArgumentException Si le montant est nul, négatif ou absent.
     */
    public function validateMontant(?float $montant): void
    {
        if ($montant === null) {
            throw new \InvalidArgumentException(
                'Le montant de la commande est obligatoire.'
            );
        }

        if ($montant < self::MONTANT_MIN) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Le montant doit être supérieur à %.2f € — valeur reçue : %.2f €.',
                    self::MONTANT_MIN,
                    $montant
                )
            );
        }
    }

    /**
     * R2 — Valide l'identifiant de session Stripe.
     *
     * @throws \InvalidArgumentException Si l'ID est vide ou trop court.
     */
    public function validateStripeSessionId(?string $sessionId): void
    {
        if (empty($sessionId) || trim($sessionId) === '') {
            throw new \InvalidArgumentException(
                'L\'identifiant de session Stripe est obligatoire.'
            );
        }

        $len = mb_strlen(trim($sessionId));

        if ($len < self::STRIPE_SESSION_MIN_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf(
                    'L\'identifiant de session Stripe doit contenir au moins %d caractères (reçu : %d).',
                    self::STRIPE_SESSION_MIN_LENGTH,
                    $len
                )
            );
        }
    }

    /**
     * R3 — Valide que la date de commande n'est pas dans le futur.
     *
     * @throws \InvalidArgumentException Si la date est manquante ou future.
     */
    public function validateDateCommande(?\DateTimeImmutable $date): void
    {
        if ($date === null) {
            throw new \InvalidArgumentException(
                'La date de commande est obligatoire.'
            );
        }

        if ($date > new \DateTimeImmutable()) {
            throw new \InvalidArgumentException(
                sprintf(
                    'La date de commande ne peut pas être dans le futur — date reçue : %s.',
                    $date->format('d/m/Y H:i:s')
                )
            );
        }
    }

    /**
     * R4 — Valide que l'article est bien associé à la commande.
     *
     * @throws \InvalidArgumentException Si l'article est absent.
     */
    public function validateArticle(Commande $commande): void
    {
        if ($commande->getArticle() === null) {
            throw new \InvalidArgumentException(
                'L\'article associé à la commande est obligatoire.'
            );
        }
    }
}
