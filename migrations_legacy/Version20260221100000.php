<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260221100000 extends AbstractMigration
{
    private function tableExists(string $tableName): bool
    {
        $sql = <<<'SQL'
SELECT COUNT(*)
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = ?
SQL;

        return (int) $this->connection->fetchOne($sql, [$tableName]) > 0;
    }

    private function columnExists(string $tableName, string $columnName): bool
    {
        $sql = <<<'SQL'
SELECT COUNT(*)
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = ?
  AND COLUMN_NAME = ?
SQL;

        return (int) $this->connection->fetchOne($sql, [$tableName, $columnName]) > 0;
    }

    public function getDescription(): string
    {
        return 'Add face_encoding column to user table for facial recognition login';
    }

    public function up(Schema $schema): void
    {
        if ($this->tableExists('user') && !$this->columnExists('user', 'face_encoding')) {
            $this->addSql('ALTER TABLE user ADD face_encoding JSON DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->tableExists('user') && $this->columnExists('user', 'face_encoding')) {
            $this->addSql('ALTER TABLE user DROP COLUMN face_encoding');
        }
    }
}
