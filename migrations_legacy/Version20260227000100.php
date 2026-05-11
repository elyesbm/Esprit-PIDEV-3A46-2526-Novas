<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260227000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add missing market stats columns to skill table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE skill ADD COLUMN IF NOT EXISTS nombre_offres_associees INT DEFAULT NULL');
        $this->addSql('ALTER TABLE skill ADD COLUMN IF NOT EXISTS score_demande INT DEFAULT NULL');
        $this->addSql('ALTER TABLE skill ADD COLUMN IF NOT EXISTS tendance_marche VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE skill ADD COLUMN IF NOT EXISTS date_mise_a_jour_stats DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE skill DROP COLUMN IF EXISTS date_mise_a_jour_stats');
        $this->addSql('ALTER TABLE skill DROP COLUMN IF EXISTS tendance_marche');
        $this->addSql('ALTER TABLE skill DROP COLUMN IF EXISTS score_demande');
        $this->addSql('ALTER TABLE skill DROP COLUMN IF EXISTS nombre_offres_associees');
    }
}

