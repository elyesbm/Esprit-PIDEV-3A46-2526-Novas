<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260222220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add market stats columns to skill table (nombre_offres_associees, score_demande, tendance_marche, date_mise_a_jour_stats)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE skill ADD nombre_offres_associees INT DEFAULT NULL');
        $this->addSql('ALTER TABLE skill ADD score_demande INT DEFAULT NULL');
        $this->addSql('ALTER TABLE skill ADD tendance_marche VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE skill ADD date_mise_a_jour_stats DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE skill DROP nombre_offres_associees');
        $this->addSql('ALTER TABLE skill DROP score_demande');
        $this->addSql('ALTER TABLE skill DROP tendance_marche');
        $this->addSql('ALTER TABLE skill DROP date_mise_a_jour_stats');
    }
}
