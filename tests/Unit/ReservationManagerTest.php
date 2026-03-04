<?php

namespace App\Tests\Unit;

use App\Entity\Atelier;
use App\Entity\Reservation;
use App\Entity\User;
use App\Service\ReservationManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * =========================================================
 * ReservationManagerTest — Tests unitaires (3 tests)
 * =========================================================
 *
 * Règles testées :
 *   R1 — Le statut doit être dans {0, 1, 2}.
 *   R2 — L'adresse e-mail doit être valide (format RFC).
 */
#[CoversClass(ReservationManager::class)]
class ReservationManagerTest extends TestCase
{
    private ReservationManager $manager;

    /**
     * Instancie le service avant chaque test.
     */
    protected function setUp(): void
    {
        $this->manager = new ReservationManager();
    }

    /**
     * Construit une Reservation valide avec User et Atelier.
     */
    private function makeValidReservation(): Reservation
    {
        $user = new User();
        $user->setNOM('Dupont');
        $user->setPRENOM('Alice');
        $user->setEMAIL('alice.dupont@novas.tn');
        $user->setROLE('ROLE_USER');
        $user->setPassword('hashed_password');

        $atelier = new Atelier();
        $atelier->setTitreAtelier('Atelier PHP avancé');
        $atelier->setDescriptionAtelier('Description de l\'atelier PHP avancé pour les tests.');
        $atelier->setTypeAtelier('Technique');
        $atelier->setCapacite(20);
        $atelier->setStatutAtelier(1);
        $atelier->setImageAtelier('default.jpg');
        $atelier->setContexteAtelier(1);
        $atelier->setDateAtelier(new \DateTime('+7 days'));

        $reservation = new Reservation();
        $reservation->setNomUser('Alice Dupont');
        $reservation->setEmailUser('alice.dupont@novas.tn');
        $reservation->setStatutReservation(0);
        $reservation->setUser($user);
        $reservation->setAtelier($atelier);
        return $reservation;
    }

    // ----------------------------------------------------------
    // ✅ TEST 1 — Cas valide
    // ----------------------------------------------------------

    /**
     * Une réservation avec des données correctes doit passer la validation.
     */
    public function testValidateReservationValide(): void
    {
        $reservation = $this->makeValidReservation();

        $result = $this->manager->validate($reservation);

        $this->assertTrue($result);
    }

    // ----------------------------------------------------------
    // ❌ TEST 2 — R1 : Statut invalide
    // ----------------------------------------------------------

    /**
     * R1 — Un statut de valeur 9 (hors des valeurs autorisées 0, 1, 2)
     * doit lever une InvalidArgumentException.
     */
    public function testValidateStatutInvalide(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/statut/i');

        $reservation = $this->makeValidReservation();
        $reservation->setStatutReservation(9); // ← valeur interdite
        $this->manager->validate($reservation);
    }

    // ----------------------------------------------------------
    // ❌ TEST 3 — R2 : Email invalide
    // ----------------------------------------------------------

    /**
     * R2 — Un e-mail mal formaté doit lever une InvalidArgumentException.
     */
    public function testValidateEmailInvalide(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/e-mail|email/i');

        $reservation = $this->makeValidReservation();
        $reservation->setEmailUser('ce-nest-pas-un-email'); // ← format invalide
        $this->manager->validate($reservation);
    }
}
