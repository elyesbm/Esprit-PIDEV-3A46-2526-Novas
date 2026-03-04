<?php

namespace App\Tests\Unit;

use App\Entity\Publication;
use App\Service\PublicationManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * =========================================================
 * PublicationManagerTest — Tests unitaires (3 tests)
 * =========================================================
 *
 * Règles testées :
 *   R1 — Le titre doit contenir entre 3 et 150 caractères.
 *   R2 — Le contenu doit contenir au moins 5 caractères.
 */
#[CoversClass(PublicationManager::class)]
class PublicationManagerTest extends TestCase
{
    private PublicationManager $manager;

    /**
     * Instancie le service avant chaque test.
     */
    protected function setUp(): void
    {
        $this->manager = new PublicationManager();
    }

    /**
     * Construit une Publication valide par défaut.
     */
    private function makeValidPublication(): Publication
    {
        $pub = new Publication();
        $pub->setTitre('Mon super article de test');
        $pub->setContenu('Voici le contenu détaillé de ma publication de test.');
        $pub->setContexte(1);
        $pub->setDateCreation(new \DateTime('-1 day'));
        $pub->setStatut(1);
        $pub->setImageAuteur('default.png');
        return $pub;
    }

    // ----------------------------------------------------------
    // ✅ TEST 1 — Cas valide
    // ----------------------------------------------------------

    /**
     * Une publication avec des données correctes doit passer la validation.
     */
    public function testValidatePublicationValide(): void
    {
        $publication = $this->makeValidPublication();

        $result = $this->manager->validate($publication);

        $this->assertTrue($result);
    }

    // ----------------------------------------------------------
    // ❌ TEST 2 — R1 : Titre trop court
    // ----------------------------------------------------------

    /**
     * R1 — Un titre de 2 caractères (inférieur au minimum de 3)
     * doit lever une InvalidArgumentException.
     */
    public function testValidateTitreTropCourt(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/titre/i');

        $publication = $this->makeValidPublication();
        $publication->setTitre('ab'); // ← 2 caractères seulement
        $this->manager->validate($publication);
    }

    // ----------------------------------------------------------
    // ❌ TEST 3 — R2 : Contenu vide
    // ----------------------------------------------------------

    /**
     * R2 — Un contenu vide doit lever une InvalidArgumentException.
     */
    public function testValidateContenuVide(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/contenu/i');

        $publication = $this->makeValidPublication();
        $publication->setContenu(''); // ← contenu vide
        $this->manager->validate($publication);
    }
}
