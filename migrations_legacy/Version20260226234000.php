<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260226234000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Normalize datetime columns for commande and historique_vue';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commande CHANGE date_commande date_commande DATETIME NOT NULL');
        $this->addSql('ALTER TABLE historique_vue CHANGE date_vue date_vue DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commande CHANGE date_commande date_commande DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE historique_vue CHANGE date_vue date_vue DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }
}

