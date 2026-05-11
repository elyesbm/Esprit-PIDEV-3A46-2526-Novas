<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\IrreversibleMigration;

final class Version20260226235000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Global schema alignment migration for remaining Doctrine deltas';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE candidature_job CHANGE statut statut ENUM('EN_ATTENTE', 'ACCEPTEE', 'REFUSEE') DEFAULT 'EN_ATTENTE' NOT NULL, CHANGE date_candidature date_candidature DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE cv_name cv_name VARCHAR(255) DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL");
        $this->addSql('ALTER TABLE commentaire CHANGE deleted_at deleted_at DATETIME DEFAULT NULL, CHANGE tree_path tree_path VARCHAR(500) DEFAULT NULL, CHANGE audio_filename audio_filename VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE cv_profile CHANGE cv_file_path cv_file_path VARCHAR(255) DEFAULT NULL, CHANGE cv_file_name cv_file_name VARCHAR(255) DEFAULT NULL, CHANGE estimated_level estimated_level VARCHAR(50) DEFAULT NULL, CHANGE main_domain main_domain VARCHAR(100) DEFAULT NULL, CHANGE technical_skills technical_skills JSON DEFAULT NULL, CHANGE soft_skills soft_skills JSON DEFAULT NULL, CHANGE weak_points weak_points JSON DEFAULT NULL, CHANGE recommendations recommendations JSON DEFAULT NULL, CHANGE evolution_simulation evolution_simulation JSON DEFAULT NULL, CHANGE raw_analysis raw_analysis JSON DEFAULT NULL, CHANGE analyzed_at analyzed_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE learning_path CHANGE type_etape type_etape VARCHAR(50) DEFAULT NULL, CHANGE url url VARCHAR(500) DEFAULT NULL');
        $this->addSql("ALTER TABLE offrejob CHANGE statut_offre statut_offre ENUM('OUVERTE', 'FERMEE', 'EXPIREE') DEFAULT 'OUVERTE' NOT NULL, CHANGE date_creation_offre date_creation_offre DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE adresse adresse VARCHAR(255) DEFAULT NULL, CHANGE latitude latitude DOUBLE PRECISION DEFAULT NULL, CHANGE longitude longitude DOUBLE PRECISION DEFAULT NULL");
        $this->addSql('ALTER TABLE publication CHANGE date_modification date_modification DATETIME DEFAULT NULL, CHANGE deleted_at deleted_at DATETIME DEFAULT NULL, CHANGE image_publication image_publication VARCHAR(255) DEFAULT NULL, CHANGE youtube_video_id youtube_video_id VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE publication_reaction CHANGE signal_reason signal_reason VARCHAR(80) DEFAULT NULL');
        $this->addSql('ALTER TABLE reservation CHANGE commentaire_reservation commentaire_reservation VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE score_history CHANGE action_detail action_detail VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE two_factor_secret two_factor_secret VARCHAR(255) DEFAULT NULL, CHANGE two_factor_enabled_at two_factor_enabled_at DATETIME DEFAULT NULL, CHANGE backup_codes backup_codes JSON DEFAULT NULL, CHANGE face_encoding face_encoding JSON DEFAULT NULL, CHANGE reset_token reset_token VARCHAR(100) DEFAULT NULL, CHANGE reset_token_expires_at reset_token_expires_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        throw new IrreversibleMigration('This migration is intentionally irreversible.');
    }
}

