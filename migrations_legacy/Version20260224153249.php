<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260224153249 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $cvProfileExists = (bool) $this->connection->fetchOne("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'cv_profile'");
        if (! $cvProfileExists) {
            $this->addSql('CREATE TABLE cv_profile (id INT AUTO_INCREMENT NOT NULL, cv_file_path VARCHAR(255) DEFAULT NULL, cv_file_name VARCHAR(255) DEFAULT NULL, cv_score INT DEFAULT NULL, maturity_score INT DEFAULT NULL, competitiveness_index INT DEFAULT NULL, estimated_level VARCHAR(50) DEFAULT NULL, main_domain VARCHAR(100) DEFAULT NULL, technical_skills JSON DEFAULT NULL, soft_skills JSON DEFAULT NULL, weak_points JSON DEFAULT NULL, recommendations JSON DEFAULT NULL, evolution_simulation JSON DEFAULT NULL, ai_summary LONGTEXT DEFAULT NULL, raw_analysis JSON DEFAULT NULL, analyzed_at DATETIME DEFAULT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_CADEF941A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
            $this->addSql('ALTER TABLE cv_profile ADD CONSTRAINT FK_CADEF941A76ED395 FOREIGN KEY (user_id) REFERENCES user (ID) ON DELETE CASCADE');
        } else {
            $cvFkExists = (bool) $this->connection->fetchOne("SELECT 1 FROM information_schema.table_constraints WHERE table_schema = DATABASE() AND table_name = 'cv_profile' AND constraint_type = 'FOREIGN KEY' AND constraint_name = 'FK_CADEF941A76ED395'");
            if (! $cvFkExists) {
                $this->addSql('ALTER TABLE cv_profile ADD CONSTRAINT FK_CADEF941A76ED395 FOREIGN KEY (user_id) REFERENCES user (ID) ON DELETE CASCADE');
            }
        }
        $scoreHistoryExists = (bool) $this->connection->fetchOne("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'score_history'");
        if (! $scoreHistoryExists) {
            $this->addSql('CREATE TABLE score_history (id INT AUTO_INCREMENT NOT NULL, old_cv_score INT DEFAULT NULL, new_cv_score INT DEFAULT NULL, old_maturity_score INT DEFAULT NULL, new_maturity_score INT DEFAULT NULL, old_competitiveness_index INT DEFAULT NULL, new_competitiveness_index INT DEFAULT NULL, action_type VARCHAR(60) NOT NULL, action_detail VARCHAR(255) DEFAULT NULL, recorded_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX idx_recorded_at (recorded_at), INDEX idx_user_id (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
            $this->addSql('ALTER TABLE score_history ADD CONSTRAINT FK_463255DFA76ED395 FOREIGN KEY (user_id) REFERENCES user (ID) ON DELETE CASCADE');
        } else {
            $scoreFkExists = (bool) $this->connection->fetchOne("SELECT 1 FROM information_schema.table_constraints WHERE table_schema = DATABASE() AND table_name = 'score_history' AND constraint_type = 'FOREIGN KEY' AND constraint_name = 'FK_463255DFA76ED395'");
            if (! $scoreFkExists) {
                $this->addSql('ALTER TABLE score_history ADD CONSTRAINT FK_463255DFA76ED395 FOREIGN KEY (user_id) REFERENCES user (ID) ON DELETE CASCADE');
            }
        }
        $skillCascadeFkExists = (bool) $this->connection->fetchOne("
            SELECT 1
            FROM information_schema.key_column_usage kcu
            JOIN information_schema.referential_constraints rc
              ON rc.constraint_schema = kcu.constraint_schema
             AND rc.constraint_name = kcu.constraint_name
             AND rc.table_name = kcu.table_name
            WHERE kcu.table_schema = DATABASE()
              AND kcu.table_name = 'skill'
              AND kcu.column_name = 'createur_id'
              AND kcu.referenced_table_name = 'user'
              AND rc.delete_rule = 'CASCADE'
            LIMIT 1
        ");
        if (! $skillCascadeFkExists) {
            $existingSkillFk = $this->connection->fetchOne("
                SELECT constraint_name
                FROM information_schema.key_column_usage
                WHERE table_schema = DATABASE()
                  AND table_name = 'skill'
                  AND column_name = 'createur_id'
                  AND referenced_table_name = 'user'
                LIMIT 1
            ");
            if (is_string($existingSkillFk) && $existingSkillFk !== '') {
                $this->addSql(sprintf('ALTER TABLE skill DROP FOREIGN KEY `%s`', $existingSkillFk));
            }
            $this->addSql('ALTER TABLE skill ADD CONSTRAINT FK_5E3DE47773A201E5 FOREIGN KEY (createur_id) REFERENCES user (ID) ON DELETE CASCADE');
        }
        $userFaceEncodingExists = (bool) $this->connection->fetchOne("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'user' AND column_name = 'face_encoding'");
        if (! $userFaceEncodingExists) {
            $this->addSql('ALTER TABLE user ADD face_encoding JSON DEFAULT NULL');
        }
        $userResetTokenExists = (bool) $this->connection->fetchOne("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'user' AND column_name = 'reset_token'");
        if (! $userResetTokenExists) {
            $this->addSql('ALTER TABLE user ADD reset_token VARCHAR(100) DEFAULT NULL');
        }
        $userResetTokenExpiresExists = (bool) $this->connection->fetchOne("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'user' AND column_name = 'reset_token_expires_at'");
        if (! $userResetTokenExpiresExists) {
            $this->addSql('ALTER TABLE user ADD reset_token_expires_at DATETIME DEFAULT NULL');
        }
        $userResetTokenIndexExists = (bool) $this->connection->fetchOne("SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'user' AND index_name = 'UNIQ_8D93D649D7C8DC19'");
        if (! $userResetTokenIndexExists) {
            $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649D7C8DC19 ON user (reset_token)');
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $cvFkExists = (bool) $this->connection->fetchOne("SELECT 1 FROM information_schema.table_constraints WHERE table_schema = DATABASE() AND table_name = 'cv_profile' AND constraint_type = 'FOREIGN KEY' AND constraint_name = 'FK_CADEF941A76ED395'");
        if ($cvFkExists) {
            $this->addSql('ALTER TABLE cv_profile DROP FOREIGN KEY FK_CADEF941A76ED395');
        }
        $scoreFkExists = (bool) $this->connection->fetchOne("SELECT 1 FROM information_schema.table_constraints WHERE table_schema = DATABASE() AND table_name = 'score_history' AND constraint_type = 'FOREIGN KEY' AND constraint_name = 'FK_463255DFA76ED395'");
        if ($scoreFkExists) {
            $this->addSql('ALTER TABLE score_history DROP FOREIGN KEY FK_463255DFA76ED395');
        }
        $this->addSql('DROP TABLE IF EXISTS cv_profile');
        $this->addSql('DROP TABLE IF EXISTS score_history');
        $this->addSql('ALTER TABLE skill DROP FOREIGN KEY FK_5E3DE47773A201E5');
        $this->addSql('ALTER TABLE skill ADD CONSTRAINT `FK_5E3DE47773A201E5` FOREIGN KEY (createur_id) REFERENCES user (ID)');
        $userResetTokenIndexExists = (bool) $this->connection->fetchOne("SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'user' AND index_name = 'UNIQ_8D93D649D7C8DC19'");
        if ($userResetTokenIndexExists) {
            $this->addSql('DROP INDEX UNIQ_8D93D649D7C8DC19 ON user');
        }
        $dropParts = [];
        $userFaceEncodingExists = (bool) $this->connection->fetchOne("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'user' AND column_name = 'face_encoding'");
        if ($userFaceEncodingExists) {
            $dropParts[] = 'DROP face_encoding';
        }
        $userResetTokenExists = (bool) $this->connection->fetchOne("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'user' AND column_name = 'reset_token'");
        if ($userResetTokenExists) {
            $dropParts[] = 'DROP reset_token';
        }
        $userResetTokenExpiresExists = (bool) $this->connection->fetchOne("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'user' AND column_name = 'reset_token_expires_at'");
        if ($userResetTokenExpiresExists) {
            $dropParts[] = 'DROP reset_token_expires_at';
        }
        if ($dropParts !== []) {
            $this->addSql('ALTER TABLE user ' . implode(', ', $dropParts));
        }
    }
}
