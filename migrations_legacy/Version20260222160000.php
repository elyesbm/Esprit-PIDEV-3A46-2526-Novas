<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Colonnes Gedmo Tree (MaterializedPath) sur commentaire : tree_level, tree_path.
 */
final class Version20260222160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Gedmo Tree (materialized path) columns to commentaire: tree_level, tree_path';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commentaire ADD tree_level INT DEFAULT NULL');
        $this->addSql('ALTER TABLE commentaire ADD tree_path VARCHAR(500) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commentaire DROP tree_level');
        $this->addSql('ALTER TABLE commentaire DROP tree_path');
    }
}
