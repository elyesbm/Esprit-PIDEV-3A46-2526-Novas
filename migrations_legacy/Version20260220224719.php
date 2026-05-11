<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260220224719 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'No-op: obsolete duplicated baseline schema migration.';
    }

    public function up(Schema $schema): void
    {
        // Intentionally empty: previous auto-generated snapshot duplicated baseline tables.
    }

    public function down(Schema $schema): void
    {
        // Intentionally empty.
    }
}
