<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260226120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Normalize datetime_immutable columns to match DBAL 4 schema comparison (remove SQL comment hints).';
    }

    public function up(Schema $schema): void
    {
        $candidatureUpdatedAtComment = $this->connection->fetchOne("
            SELECT COLUMN_COMMENT
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'candidature_job'
              AND COLUMN_NAME = 'updated_at'
        ");
        if ($candidatureUpdatedAtComment === '(DC2Type:datetime_immutable)') {
            $this->addSql('ALTER TABLE candidature_job MODIFY updated_at DATETIME DEFAULT NULL');
        }

        $offreExpirationComment = $this->connection->fetchOne("
            SELECT COLUMN_COMMENT
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'offrejob'
              AND COLUMN_NAME = 'date_expiration'
        ");
        if ($offreExpirationComment === '(DC2Type:datetime_immutable)') {
            $this->addSql('ALTER TABLE offrejob MODIFY date_expiration DATETIME NOT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $candidatureUpdatedAtComment = $this->connection->fetchOne("
            SELECT COLUMN_COMMENT
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'candidature_job'
              AND COLUMN_NAME = 'updated_at'
        ");
        if ($candidatureUpdatedAtComment !== '(DC2Type:datetime_immutable)') {
            $this->addSql("ALTER TABLE candidature_job MODIFY updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
        }

        $offreExpirationComment = $this->connection->fetchOne("
            SELECT COLUMN_COMMENT
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'offrejob'
              AND COLUMN_NAME = 'date_expiration'
        ");
        if ($offreExpirationComment !== '(DC2Type:datetime_immutable)') {
            $this->addSql("ALTER TABLE offrejob MODIFY date_expiration DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'");
        }
    }
}

