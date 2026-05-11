<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260226221500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add moderation_status to offrejob and candidature_job';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE offrejob ADD moderation_status ENUM('approved', 'pending', 'rejected') NOT NULL DEFAULT 'approved'");
        $this->addSql("ALTER TABLE candidature_job ADD moderation_status ENUM('approved', 'pending', 'rejected') NOT NULL DEFAULT 'approved'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE offrejob DROP moderation_status');
        $this->addSql('ALTER TABLE candidature_job DROP moderation_status');
    }
}

