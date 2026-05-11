<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260222200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add image_publication to publication for Imagen generated images';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE publication ADD image_publication VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE publication DROP image_publication');
    }
}
