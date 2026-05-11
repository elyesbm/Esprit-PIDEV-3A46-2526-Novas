<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260223100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add youtube_video_id to publication for optional YouTube video';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE publication ADD youtube_video_id VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE publication DROP youtube_video_id');
    }
}
