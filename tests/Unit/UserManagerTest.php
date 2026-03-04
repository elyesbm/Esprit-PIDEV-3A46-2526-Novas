<?php

namespace App\Tests\Unit;

use App\Entity\User;
use App\Service\UserManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * =========================================================
 * UserManagerTest — Tests unitaires (3 tests)
 * =========================================================
 *
 * Règles testées :
 *   R1 — Le nom doit contenir entre 2 et 50 caractères.
 *   R2 — L'adresse e-mail doit être valide (format RFC).
 */
#[CoversClass(UserManager::class)]
class UserManagerTest extends TestCase
{
    private UserManager $manager;

    /**
     * Instancie le service avant chaque test.
     */
    protected function setUp(): void
    {
        $this->manager = new UserManager();
    }

    /**
     * Construit un User valide par défaut.
     */
    private function makeValidUser(): User
    {
        $user = new User();
        $user->setNOM('Dupont');
        $user->setPRENOM('Alice');
        $user->setEMAIL('alice.dupont@novas.tn');
        $user->setROLE('ROLE_USER');
        $user->setPassword('hashed_password_123');
        $user->setNUMERO(21345678);
        return $user;
    }

    // ----------------------------------------------------------
    // ✅ TEST 1 — Cas valide
    // ----------------------------------------------------------

    /**
     * Un utilisateur avec des données correctes doit passer la validation.
     */
    public function testValidateUserValide(): void
    {
        $user = $this->makeValidUser();

        $result = $this->manager->validate($user);

        $this->assertTrue($result);
    }

    // ----------------------------------------------------------
    // ❌ TEST 2 — R1 : Nom vide
    // ----------------------------------------------------------

    /**
     * R1 — Un nom vide doit lever une InvalidArgumentException.
     */
    public function testValidateNomVide(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/nom/i');

        $user = $this->makeValidUser();
        $user->setNOM(''); // ← nom vide
        $this->manager->validate($user);
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

        $user = $this->makeValidUser();
        $user->setEMAIL('pasunemail@@novas'); // ← format invalide
        $this->manager->validate($user);
    }
}
