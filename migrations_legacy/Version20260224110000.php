<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260224110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create commande and historique_vue tables for Stripe purchase history and personalized recommendations';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE commande (id_commande INT AUTO_INCREMENT NOT NULL, id_user INT DEFAULT NULL, id_article INT NOT NULL, montant DOUBLE PRECISION NOT NULL, date_commande DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', stripe_session_id VARCHAR(255) NOT NULL, INDEX IDX_6EEAA67D6B3CA4B (id_user), INDEX IDX_6EEAA67A7294869C (id_article), UNIQUE INDEX uniq_commande_session_article (stripe_session_id, id_article), PRIMARY KEY(id_commande)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE historique_vue (id_vue INT AUTO_INCREMENT NOT NULL, id_user INT NOT NULL, id_article INT NOT NULL, date_vue DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_94C9E0E56B3CA4B (id_user), INDEX IDX_94C9E0E5A7294869C (id_article), PRIMARY KEY(id_vue)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');

        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67D6B3CA4B FOREIGN KEY (id_user) REFERENCES user (ID) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67A7294869C FOREIGN KEY (id_article) REFERENCES article (id_article) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE historique_vue ADD CONSTRAINT FK_94C9E0E56B3CA4B FOREIGN KEY (id_user) REFERENCES user (ID) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE historique_vue ADD CONSTRAINT FK_94C9E0E5A7294869C FOREIGN KEY (id_article) REFERENCES article (id_article) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67D6B3CA4B');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67A7294869C');
        $this->addSql('ALTER TABLE historique_vue DROP FOREIGN KEY FK_94C9E0E56B3CA4B');
        $this->addSql('ALTER TABLE historique_vue DROP FOREIGN KEY FK_94C9E0E5A7294869C');

        $this->addSql('DROP TABLE commande');
        $this->addSql('DROP TABLE historique_vue');
    }
}

