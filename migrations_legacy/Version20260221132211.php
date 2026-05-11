<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260221132211 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE article (id_article INT AUTO_INCREMENT NOT NULL, titre_article VARCHAR(255) NOT NULL, contenue_article VARCHAR(255) NOT NULL, image_article LONGTEXT NOT NULL, type_article VARCHAR(50) NOT NULL, prix_article DOUBLE PRECISION NOT NULL, statut_article VARCHAR(50) NOT NULL, id_etudiant INT NOT NULL, id_categorie INT NOT NULL, INDEX IDX_23A0E6621A5CE76 (id_etudiant), INDEX IDX_23A0E66C9486A13 (id_categorie), PRIMARY KEY (id_article)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE atelier (id_atelier INT AUTO_INCREMENT NOT NULL, titre_atelier VARCHAR(255) NOT NULL, description_atelier VARCHAR(255) NOT NULL, type_atelier VARCHAR(255) NOT NULL, date_atelier DATE NOT NULL, capacite INT NOT NULL, statut_atelier INT NOT NULL, image_atelier LONGTEXT NOT NULL, contexte_atelier INT NOT NULL, PRIMARY KEY (id_atelier)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE candidature_job (id_candidature INT AUTO_INCREMENT NOT NULL, message TEXT DEFAULT NULL, statut ENUM(\'EN_ATTENTE\', \'ACCEPTEE\', \'REFUSEE\') DEFAULT \'EN_ATTENTE\' NOT NULL, date_candidature DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, offre_id INT NOT NULL, candidat_id INT NOT NULL, INDEX idx_offre (offre_id), INDEX idx_candidat (candidat_id), UNIQUE INDEX uniq_offre_candidat (offre_id, candidat_id), PRIMARY KEY (id_candidature)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE categorie (id_categorie INT AUTO_INCREMENT NOT NULL, nom_categorie VARCHAR(255) NOT NULL, description_categorie VARCHAR(255) NOT NULL, PRIMARY KEY (id_categorie)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE commentaire (id_comm INT AUTO_INCREMENT NOT NULL, image LONGTEXT NOT NULL, date_creation DATETIME NOT NULL, contenu LONGTEXT NOT NULL, id_auteur INT NOT NULL, id_pub INT NOT NULL, INDEX IDX_67F068BC236D04AD (id_auteur), INDEX IDX_67F068BCC4E0D4DF (id_pub), PRIMARY KEY (id_comm)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE condidature_job (id_condidature INT AUTO_INCREMENT NOT NULL, message_condidature VARCHAR(255) NOT NULL, statut_condidature VARCHAR(50) NOT NULL, date_condidature DATE NOT NULL, offre_id INT NOT NULL, condidat_id INT NOT NULL, INDEX IDX_2F901D644CC8505A (offre_id), INDEX IDX_2F901D641619DB31 (condidat_id), PRIMARY KEY (id_condidature)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE learning_path (id_learning INT AUTO_INCREMENT NOT NULL, titre_path VARCHAR(255) NOT NULL, description_skill VARCHAR(255) NOT NULL, duree_estimee INT NOT NULL, contexte_path INT NOT NULL, statut_path INT NOT NULL, niveau_path INT NOT NULL, type_etape VARCHAR(50) DEFAULT NULL, url VARCHAR(500) DEFAULT NULL, id_skill INT NOT NULL, INDEX IDX_4D04C797B0B8A547 (id_skill), PRIMARY KEY (id_learning)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE offrejob (id_offre INT AUTO_INCREMENT NOT NULL, titre_offre VARCHAR(255) NOT NULL, description_offre TEXT NOT NULL, categorie_offre ENUM(\'TUTORAT\', \'CREATION\', \'AIDE\') NOT NULL, lieu ENUM(\'EN_LIGNE\', \'PRESENTIEL\') NOT NULL, statut_offre ENUM(\'OUVERTE\', \'FERMEE\') DEFAULT \'OUVERTE\' NOT NULL, date_creation_offre DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, createur_id INT NOT NULL, INDEX IDX_D151759273A201E5 (createur_id), PRIMARY KEY (id_offre)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE publication (id_pub INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, contenu LONGTEXT NOT NULL, image_auteur LONGTEXT NOT NULL, statut INT NOT NULL, date_creation DATETIME NOT NULL, contexte INT NOT NULL, likes INT NOT NULL, date_modification DATETIME DEFAULT NULL, id_auteur INT NOT NULL, INDEX IDX_AF3C6779236D04AD (id_auteur), PRIMARY KEY (id_pub)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE reservation (id_reservation INT AUTO_INCREMENT NOT NULL, nom_user VARCHAR(255) NOT NULL, email_user VARCHAR(255) NOT NULL, commentaire_reservation VARCHAR(255) DEFAULT NULL, statut_reservation INT NOT NULL, id_user INT NOT NULL, id_atelier INT NOT NULL, INDEX IDX_42C849556B3CA4B (id_user), INDEX IDX_42C849553F26B153 (id_atelier), PRIMARY KEY (id_reservation)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE skill (id_skill INT AUTO_INCREMENT NOT NULL, nom_skill VARCHAR(255) NOT NULL, description_skill VARCHAR(255) NOT NULL, categorie VARCHAR(255) NOT NULL, contexte_skill VARCHAR(255) NOT NULL, createur_id INT DEFAULT NULL, INDEX IDX_5E3DE47773A201E5 (createur_id), PRIMARY KEY (id_skill)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE user (ID INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, image LONGTEXT DEFAULT NULL, numero INT DEFAULT NULL, role VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, ACTIF TINYINT DEFAULT 1 NOT NULL, two_factor_secret VARCHAR(255) DEFAULT NULL, two_factor_enabled_at DATETIME DEFAULT NULL, backup_codes JSON DEFAULT NULL, PRIMARY KEY (ID)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E6621A5CE76 FOREIGN KEY (id_etudiant) REFERENCES user (ID) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E66C9486A13 FOREIGN KEY (id_categorie) REFERENCES categorie (id_categorie) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE candidature_job ADD CONSTRAINT FK_E40B48E44CC8505A FOREIGN KEY (offre_id) REFERENCES offrejob (id_offre) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE candidature_job ADD CONSTRAINT FK_E40B48E48D0EB82 FOREIGN KEY (candidat_id) REFERENCES user (ID) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BC236D04AD FOREIGN KEY (id_auteur) REFERENCES user (ID)');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BCC4E0D4DF FOREIGN KEY (id_pub) REFERENCES publication (id_pub)');
        $this->addSql('ALTER TABLE condidature_job ADD CONSTRAINT FK_2F901D644CC8505A FOREIGN KEY (offre_id) REFERENCES offrejob (id_offre)');
        $this->addSql('ALTER TABLE condidature_job ADD CONSTRAINT FK_2F901D641619DB31 FOREIGN KEY (condidat_id) REFERENCES user (ID)');
        $this->addSql('ALTER TABLE learning_path ADD CONSTRAINT FK_4D04C797B0B8A547 FOREIGN KEY (id_skill) REFERENCES skill (id_skill)');
        $this->addSql('ALTER TABLE offrejob ADD CONSTRAINT FK_D151759273A201E5 FOREIGN KEY (createur_id) REFERENCES user (ID) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE publication ADD CONSTRAINT FK_AF3C6779236D04AD FOREIGN KEY (id_auteur) REFERENCES user (ID)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C849556B3CA4B FOREIGN KEY (id_user) REFERENCES user (ID)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C849553F26B153 FOREIGN KEY (id_atelier) REFERENCES atelier (id_atelier)');
        $this->addSql('ALTER TABLE skill ADD CONSTRAINT FK_5E3DE47773A201E5 FOREIGN KEY (createur_id) REFERENCES user (ID)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE article DROP FOREIGN KEY FK_23A0E6621A5CE76');
        $this->addSql('ALTER TABLE article DROP FOREIGN KEY FK_23A0E66C9486A13');
        $this->addSql('ALTER TABLE candidature_job DROP FOREIGN KEY FK_E40B48E44CC8505A');
        $this->addSql('ALTER TABLE candidature_job DROP FOREIGN KEY FK_E40B48E48D0EB82');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BC236D04AD');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BCC4E0D4DF');
        $this->addSql('ALTER TABLE condidature_job DROP FOREIGN KEY FK_2F901D644CC8505A');
        $this->addSql('ALTER TABLE condidature_job DROP FOREIGN KEY FK_2F901D641619DB31');
        $this->addSql('ALTER TABLE learning_path DROP FOREIGN KEY FK_4D04C797B0B8A547');
        $this->addSql('ALTER TABLE offrejob DROP FOREIGN KEY FK_D151759273A201E5');
        $this->addSql('ALTER TABLE publication DROP FOREIGN KEY FK_AF3C6779236D04AD');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C849556B3CA4B');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C849553F26B153');
        $this->addSql('ALTER TABLE skill DROP FOREIGN KEY FK_5E3DE47773A201E5');
        $this->addSql('DROP TABLE article');
        $this->addSql('DROP TABLE atelier');
        $this->addSql('DROP TABLE candidature_job');
        $this->addSql('DROP TABLE categorie');
        $this->addSql('DROP TABLE commentaire');
        $this->addSql('DROP TABLE condidature_job');
        $this->addSql('DROP TABLE learning_path');
        $this->addSql('DROP TABLE offrejob');
        $this->addSql('DROP TABLE publication');
        $this->addSql('DROP TABLE reservation');
        $this->addSql('DROP TABLE skill');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
