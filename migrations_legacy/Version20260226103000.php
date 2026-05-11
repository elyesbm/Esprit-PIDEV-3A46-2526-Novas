<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260226103000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add date_expiration to offrejob and enforce expiration status transitions';
    }

    public function up(Schema $schema): void
    {
        $offreTableExists = (bool) $this->connection->fetchOne("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'offrejob'");
        if (! $offreTableExists) {
            return;
        }

        $dateExpirationExists = (bool) $this->connection->fetchOne("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'offrejob' AND column_name = 'date_expiration'");
        if (! $dateExpirationExists) {
            $this->addSql("ALTER TABLE offrejob ADD date_expiration DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
        }

        $this->addSql('UPDATE offrejob SET date_expiration = DATE_ADD(COALESCE(date_creation_offre, NOW()), INTERVAL 30 DAY) WHERE date_expiration IS NULL');
        $this->addSql("ALTER TABLE offrejob MODIFY date_expiration DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'");
        $this->addSql("ALTER TABLE offrejob MODIFY statut_offre ENUM('OUVERTE', 'FERMEE', 'EXPIREE') NOT NULL DEFAULT 'OUVERTE'");
        $this->addSql("UPDATE offrejob SET statut_offre = 'EXPIREE' WHERE statut_offre = 'OUVERTE' AND date_expiration < NOW()");
    }

    public function down(Schema $schema): void
    {
        $offreTableExists = (bool) $this->connection->fetchOne("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'offrejob'");
        if (! $offreTableExists) {
            return;
        }

        $dateExpirationExists = (bool) $this->connection->fetchOne("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'offrejob' AND column_name = 'date_expiration'");
        if ($dateExpirationExists) {
            $this->addSql('ALTER TABLE offrejob DROP COLUMN date_expiration');
        }

        $statutExists = (bool) $this->connection->fetchOne("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'offrejob' AND column_name = 'statut_offre'");
        if ($statutExists) {
            $this->addSql("UPDATE offrejob SET statut_offre = 'FERMEE' WHERE statut_offre = 'EXPIREE'");
            $this->addSql("ALTER TABLE offrejob MODIFY statut_offre ENUM('OUVERTE', 'FERMEE') NOT NULL DEFAULT 'OUVERTE'");
        }
    }
}

