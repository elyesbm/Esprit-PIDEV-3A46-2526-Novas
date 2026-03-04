<?php

namespace App\Tests\Unit;

use App\Entity\Article;
use App\Entity\Commande;
use App\Service\CommandeManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * =========================================================
 * CommandeManagerTest — Tests unitaires (3 tests)
 * =========================================================
 *
 * Règles testées :
 *   R1 — Le montant doit être strictement positif (> 0).
 *   R2 — L'identifiant de session Stripe est obligatoire.
 */
#[CoversClass(CommandeManager::class)]
class CommandeManagerTest extends TestCase
{
    private CommandeManager $manager;

    /**
     * Instancie le service avant chaque test.
     */
    protected function setUp(): void
    {
        $this->manager = new CommandeManager();
    }

    /**
     * Construit un Article stub minimal.
     */
    private function makeArticle(): Article
    {
        $article = new Article();
        $article->setTitreArticle('Article de test unitaire');
        $article->setContenueArticle('Contenu de l\'article de test pour les tests unitaires.');
        $article->setImageArticle('default.png');
        $article->setTypeArticle('formation');
        $article->setPrixArticle(29.99);
        $article->setStatutArticle('active');
        return $article;
    }

    /**
     * Construit une Commande valide par défaut.
     */
    private function makeValidCommande(): Commande
    {
        $commande = new Commande();
        $commande->setMontant(49.99);
        $commande->setStripeSessionId('cs_test_abc123xyz789ok');
        $commande->setDateCommande(new \DateTimeImmutable('-1 hour'));
        $commande->setArticle($this->makeArticle());
        return $commande;
    }

    // ----------------------------------------------------------
    // ✅ TEST 1 — Cas valide
    // ----------------------------------------------------------

    /**
     * Une commande avec des données correctes doit passer la validation.
     */
    public function testValidateCommandeValide(): void
    {
        $commande = $this->makeValidCommande();

        $result = $this->manager->validate($commande);

        $this->assertTrue($result);
    }

    // ----------------------------------------------------------
    // ❌ TEST 2 — R1 : Montant négatif
    // ----------------------------------------------------------

    /**
     * R1 — Un montant négatif doit lever une InvalidArgumentException.
     */
    public function testValidateMontantNegatif(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/montant/i');

        $this->manager->validateMontant(-10.50); // ← montant négatif
    }

    // ----------------------------------------------------------
    // ❌ TEST 3 — R2 : Stripe Session ID vide
    // ----------------------------------------------------------

    /**
     * R2 — Un identifiant de session Stripe vide doit lever une InvalidArgumentException.
     */
    public function testValidateStripeSessionIdVide(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/stripe|session/i');

        $this->manager->validateStripeSessionId(''); // ← session ID vide
    }
}
