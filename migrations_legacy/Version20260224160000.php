<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260224160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add report_count column on publication for signalements';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE publication ADD report_count INT NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE publication DROP report_count');
    }
}

