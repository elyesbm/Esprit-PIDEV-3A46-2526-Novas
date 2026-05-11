<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260226233000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Align foreign key index names for commande and historique_vue with current ORM mapping';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commande DROP INDEX IDX_6EEAA67A7294869C, ADD INDEX IDX_6EEAA67DDCA7A716 (id_article)');
        $this->addSql('ALTER TABLE historique_vue DROP INDEX IDX_94C9E0E56B3CA4B, ADD INDEX IDX_27CBE9876B3CA4B (id_user)');
        $this->addSql('ALTER TABLE historique_vue DROP INDEX IDX_94C9E0E5A7294869C, ADD INDEX IDX_27CBE987DCA7A716 (id_article)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE historique_vue DROP INDEX IDX_27CBE987DCA7A716, ADD INDEX IDX_94C9E0E5A7294869C (id_article)');
        $this->addSql('ALTER TABLE historique_vue DROP INDEX IDX_27CBE9876B3CA4B, ADD INDEX IDX_94C9E0E56B3CA4B (id_user)');
        $this->addSql('ALTER TABLE commande DROP INDEX IDX_6EEAA67DDCA7A716, ADD INDEX IDX_6EEAA67A7294869C (id_article)');
    }
}
