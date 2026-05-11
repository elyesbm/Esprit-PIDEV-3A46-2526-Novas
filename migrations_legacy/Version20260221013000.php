<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260221013000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create publication_reaction table for like/dislike per user and publication.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE publication_reaction (id_reaction INT AUTO_INCREMENT NOT NULL, id_pub INT NOT NULL, id_user INT NOT NULL, value SMALLINT NOT NULL, date_creation DATETIME NOT NULL, INDEX IDX_529ACDCFC4E0D4DF (id_pub), INDEX IDX_529ACDCF6B3CA4B (id_user), UNIQUE INDEX uniq_pub_user_reaction (id_pub, id_user), PRIMARY KEY(id_reaction)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        // FKs are added in a later migration after publication/user tables exist.
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE publication_reaction');
    }
}

