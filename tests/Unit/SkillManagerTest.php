<?php

namespace App\Tests\Unit;

use App\Entity\Skill;
use App\Service\SkillManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * =========================================================
 * SkillManagerTest — Tests unitaires (3 tests)
 * =========================================================
 *
 * Règles testées :
 *   R1 — Le nom du skill doit contenir entre 3 et 255 caractères.
 *   R2 — La catégorie est obligatoire (non vide).
 */
#[CoversClass(SkillManager::class)]
class SkillManagerTest extends TestCase
{
    private SkillManager $manager;

    /**
     * Instancie le service avant chaque test.
     */
    protected function setUp(): void
    {
        $this->manager = new SkillManager();
    }

    /**
     * Construit un Skill valide par défaut.
     */
    private function makeValidSkill(): Skill
    {
        $skill = new Skill();
        $skill->setNomSkill('Développement PHP');
        $skill->setCategorie('Informatique');
        $skill->setContexteSkill('Technique');
        $skill->setScoreDemande(4);
        $skill->setDescriptionSkill('Maîtrise du langage PHP et de son écosystème.');
        return $skill;
    }

    // ----------------------------------------------------------
    // ✅ TEST 1 — Cas valide
    // ----------------------------------------------------------

    /**
     * Un skill avec des données correctes doit passer la validation.
     */
    public function testValidateSkillValide(): void
    {
        $skill = $this->makeValidSkill();

        $result = $this->manager->validate($skill);

        $this->assertTrue($result);
    }

    // ----------------------------------------------------------
    // ❌ TEST 2 — R1 : Nom trop court
    // ----------------------------------------------------------

    /**
     * R1 — Un nom de 2 caractères (inférieur au minimum de 3)
     * doit lever une InvalidArgumentException.
     */
    public function testValidateNomSkillTropCourt(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/nom/i');

        $skill = $this->makeValidSkill();
        $skill->setNomSkill('ab'); // ← 2 caractères seulement
        $this->manager->validate($skill);
    }

    // ----------------------------------------------------------
    // ❌ TEST 3 — R2 : Catégorie vide
    // ----------------------------------------------------------

    /**
     * R2 — Une catégorie vide doit lever une InvalidArgumentException.
     */
    public function testValidateCategorieVide(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/catégorie|categorie/i');

        $skill = $this->makeValidSkill();
        $skill->setCategorie(''); // ← catégorie vide
        $this->manager->validate($skill);
    }
}
