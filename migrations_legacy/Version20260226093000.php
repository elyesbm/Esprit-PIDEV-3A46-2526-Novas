<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260226093000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add quota columns on offrejob and add EXPIREE statut';
    }

    public function up(Schema $schema): void
    {
        $offreTableExists = (bool) $this->connection->fetchOne("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'offrejob'");
        if (! $offreTableExists) {
            return;
        }

        $capaciteMaxExists = (bool) $this->connection->fetchOne("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'offrejob' AND column_name = 'capacite_max'");
        if (! $capaciteMaxExists) {
            $this->addSql('ALTER TABLE offrejob ADD capacite_max INT NOT NULL DEFAULT 5');
        }

        $capaciteRestanteExists = (bool) $this->connection->fetchOne("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'offrejob' AND column_name = 'capacite_restante'");
        if (! $capaciteRestanteExists) {
            $this->addSql('ALTER TABLE offrejob ADD capacite_restante INT NOT NULL DEFAULT 5');
        }

        $statutOffreExists = (bool) $this->connection->fetchOne("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'offrejob' AND column_name = 'statut_offre'");
        if ($statutOffreExists) {
            $this->addSql("ALTER TABLE offrejob MODIFY statut_offre ENUM('OUVERTE', 'FERMEE', 'EXPIREE') NOT NULL DEFAULT 'OUVERTE'");
        }

        $candTableExists = (bool) $this->connection->fetchOne("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'candidature_job'");
        if ($candTableExists) {
            $this->addSql("UPDATE offrejob o SET o.capacite_restante = GREATEST(0, o.capacite_max - (SELECT COUNT(*) FROM candidature_job c WHERE c.offre_id = o.id_offre AND c.statut = 'ACCEPTEE'))");
        } else {
            $this->addSql('UPDATE offrejob SET capacite_restante = capacite_max');
        }

        $this->addSql("UPDATE offrejob SET statut_offre = 'FERMEE' WHERE capacite_restante <= 0 AND statut_offre = 'OUVERTE'");
    }

    public function down(Schema $schema): void
    {
        $offreTableExists = (bool) $this->connection->fetchOne("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'offrejob'");
        if (! $offreTableExists) {
            return;
        }

        $statutOffreExists = (bool) $this->connection->fetchOne("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'offrejob' AND column_name = 'statut_offre'");
        if ($statutOffreExists) {
            $this->addSql("UPDATE offrejob SET statut_offre = 'FERMEE' WHERE statut_offre = 'EXPIREE'");
            $this->addSql("ALTER TABLE offrejob MODIFY statut_offre ENUM('OUVERTE', 'FERMEE') NOT NULL DEFAULT 'OUVERTE'");
        }

        $dropParts = [];

        $capaciteRestanteExists = (bool) $this->connection->fetchOne("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'offrejob' AND column_name = 'capacite_restante'");
        if ($capaciteRestanteExists) {
            $dropParts[] = 'DROP COLUMN capacite_restante';
        }

        $capaciteMaxExists = (bool) $this->connection->fetchOne("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'offrejob' AND column_name = 'capacite_max'");
        if ($capaciteMaxExists) {
            $dropParts[] = 'DROP COLUMN capacite_max';
        }

        if ($dropParts !== []) {
            $this->addSql('ALTER TABLE offrejob ' . implode(', ', $dropParts));
        }
    }
}
