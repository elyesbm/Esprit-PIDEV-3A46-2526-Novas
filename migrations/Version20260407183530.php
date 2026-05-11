<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260407183530 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE article (article_id INT AUTO_INCREMENT NOT NULL, titre_article VARCHAR(255) NOT NULL, contenue_article VARCHAR(255) NOT NULL, image_article LONGTEXT NOT NULL, type_article VARCHAR(50) NOT NULL, prix_article DOUBLE PRECISION NOT NULL, statut_article VARCHAR(50) NOT NULL, vues_article INT DEFAULT 0 NOT NULL, etudiant_id INT NOT NULL, categorie_id INT NOT NULL, INDEX IDX_23A0E66DDEAB1A3 (etudiant_id), INDEX IDX_23A0E66BCF5E72D (categorie_id), PRIMARY KEY (article_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE atelier (atelier_id INT AUTO_INCREMENT NOT NULL, titre_atelier VARCHAR(255) NOT NULL, description_atelier VARCHAR(255) NOT NULL, type_atelier VARCHAR(255) NOT NULL, date_atelier DATE NOT NULL, capacite INT NOT NULL, statut_atelier INT NOT NULL, image_atelier LONGTEXT NOT NULL, contexte_atelier INT NOT NULL, PRIMARY KEY (atelier_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE candidature_job (candidature_id INT AUTO_INCREMENT NOT NULL, message TEXT DEFAULT NULL, statut ENUM(\'EN_ATTENTE\', \'ACCEPTEE\', \'REFUSEE\') DEFAULT \'EN_ATTENTE\' NOT NULL, date_candidature DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, cv_name VARCHAR(255) DEFAULT NULL, updated_at DATETIME DEFAULT NULL, moderation_status ENUM(\'approved\', \'pending\', \'rejected\') DEFAULT \'approved\' NOT NULL, offre_id INT NOT NULL, candidat_id INT NOT NULL, INDEX idx_offre (offre_id), INDEX idx_candidat (candidat_id), UNIQUE INDEX uniq_offre_candidat (offre_id, candidat_id), PRIMARY KEY (candidature_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE categorie (categorie_id INT AUTO_INCREMENT NOT NULL, nom_categorie VARCHAR(255) NOT NULL, description_categorie VARCHAR(255) NOT NULL, PRIMARY KEY (categorie_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE commande (commande_id INT AUTO_INCREMENT NOT NULL, montant DOUBLE PRECISION NOT NULL, date_commande DATETIME NOT NULL, stripe_session_id VARCHAR(255) NOT NULL, user_id INT DEFAULT NULL, article_id INT NOT NULL, INDEX IDX_6EEAA67DA76ED395 (user_id), INDEX IDX_6EEAA67D7294869C (article_id), UNIQUE INDEX uniq_commande_session_article (stripe_session_id, article_id), PRIMARY KEY (commande_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE commentaire (comm_id INT AUTO_INCREMENT NOT NULL, image LONGTEXT NOT NULL, date_creation DATETIME NOT NULL, contenu LONGTEXT NOT NULL, tree_level INT DEFAULT NULL, tree_path VARCHAR(500) DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, audio_filename VARCHAR(255) DEFAULT NULL, auteur_id INT NOT NULL, pub_id INT NOT NULL, parent_id INT DEFAULT NULL, INDEX IDX_67F068BC60BB6FE6 (auteur_id), INDEX IDX_67F068BC83FDE077 (pub_id), INDEX IDX_67F068BC727ACA70 (parent_id), PRIMARY KEY (comm_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE condidature_job (condidature_id INT AUTO_INCREMENT NOT NULL, message_condidature VARCHAR(255) NOT NULL, statut_condidature VARCHAR(50) NOT NULL, date_condidature DATE NOT NULL, offre_id INT NOT NULL, condidat_id INT NOT NULL, INDEX IDX_2F901D644CC8505A (offre_id), INDEX IDX_2F901D641619DB31 (condidat_id), PRIMARY KEY (condidature_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE cv_profile (id INT AUTO_INCREMENT NOT NULL, cv_file_path VARCHAR(255) DEFAULT NULL, cv_file_name VARCHAR(255) DEFAULT NULL, cv_score INT DEFAULT NULL, maturity_score INT DEFAULT NULL, competitiveness_index INT DEFAULT NULL, estimated_level VARCHAR(50) DEFAULT NULL, main_domain VARCHAR(100) DEFAULT NULL, technical_skills JSON DEFAULT NULL, soft_skills JSON DEFAULT NULL, weak_points JSON DEFAULT NULL, recommendations JSON DEFAULT NULL, evolution_simulation JSON DEFAULT NULL, ai_summary LONGTEXT DEFAULT NULL, raw_analysis JSON DEFAULT NULL, analyzed_at DATETIME DEFAULT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_CADEF941A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE historique_vue (vue_id INT AUTO_INCREMENT NOT NULL, date_vue DATETIME NOT NULL, user_id INT NOT NULL, article_id INT NOT NULL, INDEX IDX_27CBE987A76ED395 (user_id), INDEX IDX_27CBE9877294869C (article_id), PRIMARY KEY (vue_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE learning_path (learning_id INT AUTO_INCREMENT NOT NULL, titre_path VARCHAR(255) NOT NULL, description_skill VARCHAR(255) NOT NULL, duree_estimee INT NOT NULL, contexte_path INT NOT NULL, statut_path INT NOT NULL, niveau_path INT NOT NULL, type_etape VARCHAR(50) DEFAULT NULL, url VARCHAR(500) DEFAULT NULL, skill_id INT NOT NULL, INDEX IDX_4D04C7975585C142 (skill_id), PRIMARY KEY (learning_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE offrejob (offre_id INT AUTO_INCREMENT NOT NULL, titre_offre VARCHAR(255) NOT NULL, description_offre TEXT NOT NULL, categorie_offre ENUM(\'TUTORAT\', \'CREATION\', \'AIDE\') NOT NULL, lieu ENUM(\'EN_LIGNE\', \'PRESENTIEL\') NOT NULL, statut_offre ENUM(\'OUVERTE\', \'FERMEE\', \'EXPIREE\') DEFAULT \'OUVERTE\' NOT NULL, capacite_max INT DEFAULT 5 NOT NULL, capacite_restante INT DEFAULT 5 NOT NULL, date_expiration DATETIME NOT NULL, moderation_status ENUM(\'approved\', \'pending\', \'rejected\') DEFAULT \'approved\' NOT NULL, adresse VARCHAR(255) DEFAULT NULL, latitude DOUBLE PRECISION DEFAULT NULL, longitude DOUBLE PRECISION DEFAULT NULL, date_creation_offre DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, createur_id INT NOT NULL, INDEX IDX_D151759273A201E5 (createur_id), PRIMARY KEY (offre_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE publication (pub_id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, contenu LONGTEXT NOT NULL, image_auteur LONGTEXT NOT NULL, image_publication VARCHAR(255) DEFAULT NULL, youtube_video_id VARCHAR(20) DEFAULT NULL, statut INT NOT NULL, date_creation DATETIME NOT NULL, contexte INT NOT NULL, likes INT NOT NULL, report_count INT NOT NULL, date_modification DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, auteur_id INT NOT NULL, INDEX IDX_AF3C677960BB6FE6 (auteur_id), PRIMARY KEY (pub_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE publication_reaction (reaction_id INT AUTO_INCREMENT NOT NULL, value SMALLINT NOT NULL, date_creation DATETIME NOT NULL, signal_reason VARCHAR(80) DEFAULT NULL, pub_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_8BB0F2A683FDE077 (pub_id), INDEX IDX_8BB0F2A6A76ED395 (user_id), UNIQUE INDEX uniq_pub_user_reaction (pub_id, user_id), PRIMARY KEY (reaction_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE reservation (reservation_id INT AUTO_INCREMENT NOT NULL, nom_user VARCHAR(255) NOT NULL, email_user VARCHAR(255) NOT NULL, commentaire_reservation VARCHAR(255) DEFAULT NULL, statut_reservation INT NOT NULL, user_id INT NOT NULL, atelier_id INT NOT NULL, INDEX IDX_42C84955A76ED395 (user_id), INDEX IDX_42C8495582E2CF35 (atelier_id), PRIMARY KEY (reservation_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE score_history (id INT AUTO_INCREMENT NOT NULL, old_cv_score INT DEFAULT NULL, new_cv_score INT DEFAULT NULL, old_maturity_score INT DEFAULT NULL, new_maturity_score INT DEFAULT NULL, old_competitiveness_index INT DEFAULT NULL, new_competitiveness_index INT DEFAULT NULL, action_type VARCHAR(60) NOT NULL, action_detail VARCHAR(255) DEFAULT NULL, recorded_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX idx_recorded_at (recorded_at), INDEX idx_user_id (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE skill (skill_id INT AUTO_INCREMENT NOT NULL, nom_skill VARCHAR(255) NOT NULL, description_skill VARCHAR(255) NOT NULL, categorie VARCHAR(255) NOT NULL, contexte_skill VARCHAR(255) NOT NULL, nombre_offres_associees INT DEFAULT NULL, score_demande INT DEFAULT NULL, tendance_marche VARCHAR(20) DEFAULT NULL, date_mise_a_jour_stats DATETIME DEFAULT NULL, createur_id INT DEFAULT NULL, INDEX IDX_5E3DE47773A201E5 (createur_id), PRIMARY KEY (skill_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, image LONGTEXT DEFAULT NULL, numero INT DEFAULT NULL, role VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, actif TINYINT DEFAULT 1 NOT NULL, two_factor_secret VARCHAR(255) DEFAULT NULL, two_factor_enabled_at DATETIME DEFAULT NULL, backup_codes JSON DEFAULT NULL, face_encoding JSON DEFAULT NULL, reset_token VARCHAR(100) DEFAULT NULL, reset_token_expires_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649D7C8DC19 (reset_token), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E66DDEAB1A3 FOREIGN KEY (etudiant_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E66BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (categorie_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE candidature_job ADD CONSTRAINT FK_E40B48E44CC8505A FOREIGN KEY (offre_id) REFERENCES offrejob (offre_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE candidature_job ADD CONSTRAINT FK_E40B48E48D0EB82 FOREIGN KEY (candidat_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67D7294869C FOREIGN KEY (article_id) REFERENCES article (article_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BC60BB6FE6 FOREIGN KEY (auteur_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BC83FDE077 FOREIGN KEY (pub_id) REFERENCES publication (pub_id)');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BC727ACA70 FOREIGN KEY (parent_id) REFERENCES commentaire (comm_id)');
        $this->addSql('ALTER TABLE condidature_job ADD CONSTRAINT FK_2F901D644CC8505A FOREIGN KEY (offre_id) REFERENCES offrejob (offre_id)');
        $this->addSql('ALTER TABLE condidature_job ADD CONSTRAINT FK_2F901D641619DB31 FOREIGN KEY (condidat_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE cv_profile ADD CONSTRAINT FK_CADEF941A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE historique_vue ADD CONSTRAINT FK_27CBE987A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE historique_vue ADD CONSTRAINT FK_27CBE9877294869C FOREIGN KEY (article_id) REFERENCES article (article_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE learning_path ADD CONSTRAINT FK_4D04C7975585C142 FOREIGN KEY (skill_id) REFERENCES skill (skill_id)');
        $this->addSql('ALTER TABLE offrejob ADD CONSTRAINT FK_D151759273A201E5 FOREIGN KEY (createur_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE publication ADD CONSTRAINT FK_AF3C677960BB6FE6 FOREIGN KEY (auteur_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE publication_reaction ADD CONSTRAINT FK_8BB0F2A683FDE077 FOREIGN KEY (pub_id) REFERENCES publication (pub_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE publication_reaction ADD CONSTRAINT FK_8BB0F2A6A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C8495582E2CF35 FOREIGN KEY (atelier_id) REFERENCES atelier (atelier_id)');
        $this->addSql('ALTER TABLE score_history ADD CONSTRAINT FK_463255DFA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE skill ADD CONSTRAINT FK_5E3DE47773A201E5 FOREIGN KEY (createur_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE article DROP FOREIGN KEY FK_23A0E66DDEAB1A3');
        $this->addSql('ALTER TABLE article DROP FOREIGN KEY FK_23A0E66BCF5E72D');
        $this->addSql('ALTER TABLE candidature_job DROP FOREIGN KEY FK_E40B48E44CC8505A');
        $this->addSql('ALTER TABLE candidature_job DROP FOREIGN KEY FK_E40B48E48D0EB82');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DA76ED395');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67D7294869C');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BC60BB6FE6');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BC83FDE077');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BC727ACA70');
        $this->addSql('ALTER TABLE condidature_job DROP FOREIGN KEY FK_2F901D644CC8505A');
        $this->addSql('ALTER TABLE condidature_job DROP FOREIGN KEY FK_2F901D641619DB31');
        $this->addSql('ALTER TABLE cv_profile DROP FOREIGN KEY FK_CADEF941A76ED395');
        $this->addSql('ALTER TABLE historique_vue DROP FOREIGN KEY FK_27CBE987A76ED395');
        $this->addSql('ALTER TABLE historique_vue DROP FOREIGN KEY FK_27CBE9877294869C');
        $this->addSql('ALTER TABLE learning_path DROP FOREIGN KEY FK_4D04C7975585C142');
        $this->addSql('ALTER TABLE offrejob DROP FOREIGN KEY FK_D151759273A201E5');
        $this->addSql('ALTER TABLE publication DROP FOREIGN KEY FK_AF3C677960BB6FE6');
        $this->addSql('ALTER TABLE publication_reaction DROP FOREIGN KEY FK_8BB0F2A683FDE077');
        $this->addSql('ALTER TABLE publication_reaction DROP FOREIGN KEY FK_8BB0F2A6A76ED395');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955A76ED395');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C8495582E2CF35');
        $this->addSql('ALTER TABLE score_history DROP FOREIGN KEY FK_463255DFA76ED395');
        $this->addSql('ALTER TABLE skill DROP FOREIGN KEY FK_5E3DE47773A201E5');
        $this->addSql('DROP TABLE article');
        $this->addSql('DROP TABLE atelier');
        $this->addSql('DROP TABLE candidature_job');
        $this->addSql('DROP TABLE categorie');
        $this->addSql('DROP TABLE commande');
        $this->addSql('DROP TABLE commentaire');
        $this->addSql('DROP TABLE condidature_job');
        $this->addSql('DROP TABLE cv_profile');
        $this->addSql('DROP TABLE historique_vue');
        $this->addSql('DROP TABLE learning_path');
        $this->addSql('DROP TABLE offrejob');
        $this->addSql('DROP TABLE publication');
        $this->addSql('DROP TABLE publication_reaction');
        $this->addSql('DROP TABLE reservation');
        $this->addSql('DROP TABLE score_history');
        $this->addSql('DROP TABLE skill');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
