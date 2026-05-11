<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260223143000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add vues_article column on article for popularity stats';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE article ADD vues_article INT NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE article DROP vues_article');
    }
}

