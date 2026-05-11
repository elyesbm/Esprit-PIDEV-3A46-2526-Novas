<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260226193000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add adresse, latitude and longitude fields to offrejob';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE offrejob ADD adresse VARCHAR(255) DEFAULT NULL, ADD latitude DOUBLE PRECISION DEFAULT NULL, ADD longitude DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE offrejob DROP adresse, DROP latitude, DROP longitude');
    }
}

