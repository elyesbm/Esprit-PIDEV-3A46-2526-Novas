<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260222150215 extends AbstractMigration
{
    private function foreignKeyExists(string $tableName, string $constraintName): bool
    {
        $sql = <<<'SQL'
SELECT COUNT(*)
FROM information_schema.TABLE_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA = DATABASE()
  AND TABLE_NAME = ?
  AND CONSTRAINT_NAME = ?
  AND CONSTRAINT_TYPE = 'FOREIGN KEY'
SQL;

        return (int) $this->connection->fetchOne($sql, [$tableName, $constraintName]) > 0;
    }

    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commentaire ADD deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE publication ADD deleted_at DATETIME DEFAULT NULL');
        if ($this->foreignKeyExists('publication_reaction', 'FK_529ACDCF6B3CA4B')) {
            $this->addSql('ALTER TABLE publication_reaction DROP FOREIGN KEY `FK_529ACDCF6B3CA4B`');
        }
        if ($this->foreignKeyExists('publication_reaction', 'FK_529ACDCFC4E0D4DF')) {
            $this->addSql('ALTER TABLE publication_reaction DROP FOREIGN KEY `FK_529ACDCFC4E0D4DF`');
        }
        $this->addSql('DROP INDEX idx_529acdcfc4e0d4df ON publication_reaction');
        $this->addSql('CREATE INDEX IDX_8BB0F2A6C4E0D4DF ON publication_reaction (id_pub)');
        $this->addSql('DROP INDEX idx_529acdcf6b3ca4b ON publication_reaction');
        $this->addSql('CREATE INDEX IDX_8BB0F2A66B3CA4B ON publication_reaction (id_user)');
        $this->addSql('ALTER TABLE publication_reaction ADD CONSTRAINT `FK_529ACDCF6B3CA4B` FOREIGN KEY (id_user) REFERENCES user (ID) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE publication_reaction ADD CONSTRAINT `FK_529ACDCFC4E0D4DF` FOREIGN KEY (id_pub) REFERENCES publication (id_pub) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commentaire DROP deleted_at');
        $this->addSql('ALTER TABLE publication DROP deleted_at');
        $this->addSql('ALTER TABLE publication_reaction DROP FOREIGN KEY FK_8BB0F2A6C4E0D4DF');
        $this->addSql('ALTER TABLE publication_reaction DROP FOREIGN KEY FK_8BB0F2A66B3CA4B');
        $this->addSql('DROP INDEX idx_8bb0f2a6c4e0d4df ON publication_reaction');
        $this->addSql('CREATE INDEX IDX_529ACDCFC4E0D4DF ON publication_reaction (id_pub)');
        $this->addSql('DROP INDEX idx_8bb0f2a66b3ca4b ON publication_reaction');
        $this->addSql('CREATE INDEX IDX_529ACDCF6B3CA4B ON publication_reaction (id_user)');
        $this->addSql('ALTER TABLE publication_reaction ADD CONSTRAINT FK_8BB0F2A6C4E0D4DF FOREIGN KEY (id_pub) REFERENCES publication (id_pub) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE publication_reaction ADD CONSTRAINT FK_8BB0F2A66B3CA4B FOREIGN KEY (id_user) REFERENCES user (ID) ON DELETE CASCADE');
    }
}
