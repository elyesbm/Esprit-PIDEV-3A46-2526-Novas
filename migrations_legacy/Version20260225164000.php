<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260225164000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add CV upload fields to candidature_job (cv_name, updated_at)';
    }

    public function up(Schema $schema): void
    {
        $cvNameExists = (bool) $this->connection->fetchOne("
            SELECT 1
            FROM information_schema.columns
            WHERE table_schema = DATABASE()
              AND table_name = 'candidature_job'
              AND column_name = 'cv_name'
        ");

        if (! $cvNameExists) {
            $this->addSql('ALTER TABLE candidature_job ADD cv_name VARCHAR(255) DEFAULT NULL');
        }

        $updatedAtExists = (bool) $this->connection->fetchOne("
            SELECT 1
            FROM information_schema.columns
            WHERE table_schema = DATABASE()
              AND table_name = 'candidature_job'
              AND column_name = 'updated_at'
        ");

        if (! $updatedAtExists) {
            $this->addSql("ALTER TABLE candidature_job ADD updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
        }
    }

    public function down(Schema $schema): void
    {
        $dropParts = [];

        $cvNameExists = (bool) $this->connection->fetchOne("
            SELECT 1
            FROM information_schema.columns
            WHERE table_schema = DATABASE()
              AND table_name = 'candidature_job'
              AND column_name = 'cv_name'
        ");

        if ($cvNameExists) {
            $dropParts[] = 'DROP cv_name';
        }

        $updatedAtExists = (bool) $this->connection->fetchOne("
            SELECT 1
            FROM information_schema.columns
            WHERE table_schema = DATABASE()
              AND table_name = 'candidature_job'
              AND column_name = 'updated_at'
        ");

        if ($updatedAtExists) {
            $dropParts[] = 'DROP updated_at';
        }

        if ($dropParts !== []) {
            $this->addSql('ALTER TABLE candidature_job ' . implode(', ', $dropParts));
        }
    }
}
