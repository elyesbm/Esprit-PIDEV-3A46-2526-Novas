<?php

namespace App\Tests\Unit;

use App\Entity\Offrejob;
use App\Enum\OffreCategorie;
use App\Enum\OffreLieu;
use App\Enum\OffreStatut;
use App\Service\OffrejobManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * =========================================================
 * OffrejobManagerTest — Tests unitaires (3 tests)
 * =========================================================
 *
 * Règles testées :
 *   R1 — La capacité maximale doit être strictement positive (> 0).
 *   R2 — La date d'expiration doit être dans le futur.
 */
#[CoversClass(OffrejobManager::class)]
class OffrejobManagerTest extends TestCase
{
    private OffrejobManager $manager;

    /**
     * Instancie le service avant chaque test.
     */
    protected function setUp(): void
    {
        $this->manager = new OffrejobManager();
    }

    /**
     * Construit une Offrejob valide par défaut.
     */
    private function makeValidOffrejob(): Offrejob
    {
        $offre = new Offrejob();
        $offre->setTitreOffre('Offre Développeur PHP Senior');
        $offre->setDescriptionOffre('Description détaillée de l\'offre d\'emploi.');
        $offre->setCategorieOffre(OffreCategorie::TUTORAT);
        $offre->setLieu(OffreLieu::EN_LIGNE);
        $offre->setStatutOffre(OffreStatut::OUVERTE);
        $offre->setCapaciteMax(10);
        $offre->setCapaciteRestante(10);
        $offre->setDateExpiration(new \DateTimeImmutable('+30 days'));
        $offre->setDateCreationOffre(new \DateTime());
        return $offre;
    }

    // ----------------------------------------------------------
    // ✅ TEST 1 — Cas valide
    // ----------------------------------------------------------

    /**
     * Une offre avec des données correctes doit passer la validation.
     */
    public function testValidateOffrejobValide(): void
    {
        $offre = $this->makeValidOffrejob();

        $result = $this->manager->validate($offre);

        $this->assertTrue($result);
    }

    // ----------------------------------------------------------
    // ❌ TEST 2 — R1 : Capacité maximale nulle
    // ----------------------------------------------------------

    /**
     * R1 — Une capacité maximale de 0 doit lever une InvalidArgumentException.
     */
    public function testValidateCapaciteMaxNulle(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/capacité|capacite/i');

        // On appelle directement la méthode de validation individuelle
        // car setCapaciteMax() lève déjà une exception dans l'entité elle-même
        $this->manager->validateCapaciteMax(0); // ← capacité = 0
    }

    // ----------------------------------------------------------
    // ❌ TEST 3 — R2 : Date d'expiration passée
    // ----------------------------------------------------------

    /**
     * R2 — Une date d'expiration dans le passé doit lever une InvalidArgumentException.
     */
    public function testValidateDateExpirationPassee(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/futur|expiration/i');

        $datePassee = new \DateTimeImmutable('-1 day'); // ← date passée
        $this->manager->validateDateExpiration($datePassee);
    }
}
