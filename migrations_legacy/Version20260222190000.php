<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260222190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add audio_filename to commentaire for voice recording';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commentaire ADD audio_filename VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commentaire DROP audio_filename');
    }
}
