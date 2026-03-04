<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260304131647 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE article ADD article_id INT AUTO_INCREMENT NOT NULL, ADD etudiant_id INT NOT NULL, ADD categorie_id INT NOT NULL, DROP id_article, DROP id_etudiant, DROP id_categorie, DROP PRIMARY KEY, ADD PRIMARY KEY (article_id)');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E66DDEAB1A3 FOREIGN KEY (etudiant_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E66BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (categorie_id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_23A0E66DDEAB1A3 ON article (etudiant_id)');
        $this->addSql('CREATE INDEX IDX_23A0E66BCF5E72D ON article (categorie_id)');
        $this->addSql('ALTER TABLE atelier MODIFY id_atelier INT NOT NULL');
        $this->addSql('ALTER TABLE atelier CHANGE id_atelier atelier_id INT AUTO_INCREMENT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (atelier_id)');
        $this->addSql('ALTER TABLE candidature_job DROP FOREIGN KEY `FK_E40B48E44CC8505A`');
        $this->addSql('ALTER TABLE candidature_job MODIFY id_candidature INT NOT NULL');
        $this->addSql('ALTER TABLE candidature_job CHANGE id_candidature candidature_id INT AUTO_INCREMENT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (candidature_id)');
        $this->addSql('ALTER TABLE candidature_job ADD CONSTRAINT FK_E40B48E44CC8505A FOREIGN KEY (offre_id) REFERENCES offrejob (offre_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE categorie MODIFY id_categorie INT NOT NULL');
        $this->addSql('ALTER TABLE categorie CHANGE id_categorie categorie_id INT AUTO_INCREMENT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (categorie_id)');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY `FK_6EEAA67A7294869C`');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY `FK_6EEAA67D6B3CA4B`');
        $this->addSql('DROP INDEX IDX_6EEAA67DDCA7A716 ON commande');
        $this->addSql('DROP INDEX uniq_commande_session_article ON commande');
        $this->addSql('DROP INDEX IDX_6EEAA67D6B3CA4B ON commande');
        $this->addSql('ALTER TABLE commande MODIFY id_commande INT NOT NULL');
        $this->addSql('ALTER TABLE commande CHANGE id_commande commande_id INT AUTO_INCREMENT NOT NULL, CHANGE id_user user_id INT DEFAULT NULL, CHANGE id_article article_id INT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (commande_id)');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67D7294869C FOREIGN KEY (article_id) REFERENCES article (article_id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_6EEAA67DA76ED395 ON commande (user_id)');
        $this->addSql('CREATE INDEX IDX_6EEAA67D7294869C ON commande (article_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_commande_session_article ON commande (stripe_session_id, article_id)');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY `FK_67F068BC1BB9D5A2`');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY `FK_67F068BC236D04AD`');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY `FK_67F068BCC4E0D4DF`');
        $this->addSql('DROP INDEX IDX_67F068BCC4E0D4DF ON commentaire');
        $this->addSql('DROP INDEX IDX_67F068BC1BB9D5A2 ON commentaire');
        $this->addSql('DROP INDEX IDX_67F068BC236D04AD ON commentaire');
        $this->addSql('ALTER TABLE commentaire MODIFY id_comm INT NOT NULL');
        $this->addSql('ALTER TABLE commentaire ADD auteur_id INT NOT NULL, ADD pub_id INT NOT NULL, DROP id_auteur, DROP id_pub, CHANGE id_comm comm_id INT AUTO_INCREMENT NOT NULL, CHANGE id_parent parent_id INT DEFAULT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (comm_id)');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BC60BB6FE6 FOREIGN KEY (auteur_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BC83FDE077 FOREIGN KEY (pub_id) REFERENCES publication (pub_id)');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BC727ACA70 FOREIGN KEY (parent_id) REFERENCES commentaire (comm_id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_67F068BC60BB6FE6 ON commentaire (auteur_id)');
        $this->addSql('CREATE INDEX IDX_67F068BC83FDE077 ON commentaire (pub_id)');
        $this->addSql('CREATE INDEX IDX_67F068BC727ACA70 ON commentaire (parent_id)');
        $this->addSql('ALTER TABLE condidature_job DROP FOREIGN KEY `FK_2F901D644CC8505A`');
        $this->addSql('ALTER TABLE condidature_job MODIFY id_condidature INT NOT NULL');
        $this->addSql('ALTER TABLE condidature_job CHANGE id_condidature condidature_id INT AUTO_INCREMENT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (condidature_id)');
        $this->addSql('ALTER TABLE condidature_job ADD CONSTRAINT FK_2F901D644CC8505A FOREIGN KEY (offre_id) REFERENCES offrejob (offre_id)');
        $this->addSql('ALTER TABLE historique_vue DROP FOREIGN KEY `FK_94C9E0E56B3CA4B`');
        $this->addSql('ALTER TABLE historique_vue DROP FOREIGN KEY `FK_94C9E0E5A7294869C`');
        $this->addSql('DROP INDEX IDX_27CBE987DCA7A716 ON historique_vue');
        $this->addSql('DROP INDEX IDX_27CBE9876B3CA4B ON historique_vue');
        $this->addSql('ALTER TABLE historique_vue MODIFY id_vue INT NOT NULL');
        $this->addSql('ALTER TABLE historique_vue ADD user_id INT NOT NULL, ADD article_id INT NOT NULL, DROP id_user, DROP id_article, CHANGE id_vue vue_id INT AUTO_INCREMENT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (vue_id)');
        $this->addSql('ALTER TABLE historique_vue ADD CONSTRAINT FK_27CBE987A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE historique_vue ADD CONSTRAINT FK_27CBE9877294869C FOREIGN KEY (article_id) REFERENCES article (article_id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_27CBE987A76ED395 ON historique_vue (user_id)');
        $this->addSql('CREATE INDEX IDX_27CBE9877294869C ON historique_vue (article_id)');
        $this->addSql('ALTER TABLE learning_path DROP FOREIGN KEY `FK_4D04C797B0B8A547`');
        $this->addSql('DROP INDEX IDX_4D04C797B0B8A547 ON learning_path');
        $this->addSql('ALTER TABLE learning_path MODIFY id_learning INT NOT NULL');
        $this->addSql('ALTER TABLE learning_path CHANGE id_learning learning_id INT AUTO_INCREMENT NOT NULL, CHANGE id_skill skill_id INT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (learning_id)');
        $this->addSql('ALTER TABLE learning_path ADD CONSTRAINT FK_4D04C7975585C142 FOREIGN KEY (skill_id) REFERENCES skill (skill_id)');
        $this->addSql('CREATE INDEX IDX_4D04C7975585C142 ON learning_path (skill_id)');
        $this->addSql('ALTER TABLE offrejob MODIFY id_offre INT NOT NULL');
        $this->addSql('ALTER TABLE offrejob CHANGE id_offre offre_id INT AUTO_INCREMENT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (offre_id)');
        $this->addSql('ALTER TABLE publication DROP FOREIGN KEY `FK_AF3C6779236D04AD`');
        $this->addSql('DROP INDEX IDX_AF3C6779236D04AD ON publication');
        $this->addSql('ALTER TABLE publication MODIFY id_pub INT NOT NULL');
        $this->addSql('ALTER TABLE publication CHANGE id_pub pub_id INT AUTO_INCREMENT NOT NULL, CHANGE id_auteur auteur_id INT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (pub_id)');
        $this->addSql('ALTER TABLE publication ADD CONSTRAINT FK_AF3C677960BB6FE6 FOREIGN KEY (auteur_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_AF3C677960BB6FE6 ON publication (auteur_id)');
        $this->addSql('ALTER TABLE publication_reaction DROP FOREIGN KEY `FK_529ACDCF6B3CA4B`');
        $this->addSql('ALTER TABLE publication_reaction DROP FOREIGN KEY `FK_529ACDCFC4E0D4DF`');
        $this->addSql('DROP INDEX IDX_8BB0F2A66B3CA4B ON publication_reaction');
        $this->addSql('DROP INDEX uniq_pub_user_reaction ON publication_reaction');
        $this->addSql('DROP INDEX IDX_8BB0F2A6C4E0D4DF ON publication_reaction');
        $this->addSql('ALTER TABLE publication_reaction MODIFY id_reaction INT NOT NULL');
        $this->addSql('ALTER TABLE publication_reaction ADD pub_id INT NOT NULL, ADD user_id INT NOT NULL, DROP id_pub, DROP id_user, CHANGE id_reaction reaction_id INT AUTO_INCREMENT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (reaction_id)');
        $this->addSql('ALTER TABLE publication_reaction ADD CONSTRAINT FK_8BB0F2A683FDE077 FOREIGN KEY (pub_id) REFERENCES publication (pub_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE publication_reaction ADD CONSTRAINT FK_8BB0F2A6A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_8BB0F2A683FDE077 ON publication_reaction (pub_id)');
        $this->addSql('CREATE INDEX IDX_8BB0F2A6A76ED395 ON publication_reaction (user_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_pub_user_reaction ON publication_reaction (pub_id, user_id)');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY `FK_42C849553F26B153`');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY `FK_42C849556B3CA4B`');
        $this->addSql('DROP INDEX IDX_42C849553F26B153 ON reservation');
        $this->addSql('DROP INDEX IDX_42C849556B3CA4B ON reservation');
        $this->addSql('ALTER TABLE reservation MODIFY id_reservation INT NOT NULL');
        $this->addSql('ALTER TABLE reservation ADD user_id INT NOT NULL, ADD atelier_id INT NOT NULL, DROP id_user, DROP id_atelier, CHANGE id_reservation reservation_id INT AUTO_INCREMENT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (reservation_id)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C8495582E2CF35 FOREIGN KEY (atelier_id) REFERENCES atelier (atelier_id)');
        $this->addSql('CREATE INDEX IDX_42C84955A76ED395 ON reservation (user_id)');
        $this->addSql('CREATE INDEX IDX_42C8495582E2CF35 ON reservation (atelier_id)');
        $this->addSql('ALTER TABLE skill MODIFY id_skill INT NOT NULL');
        $this->addSql('ALTER TABLE skill CHANGE id_skill skill_id INT AUTO_INCREMENT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (skill_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE article DROP FOREIGN KEY FK_23A0E66DDEAB1A3');
        $this->addSql('ALTER TABLE article DROP FOREIGN KEY FK_23A0E66BCF5E72D');
        $this->addSql('DROP INDEX IDX_23A0E66DDEAB1A3 ON article');
        $this->addSql('DROP INDEX IDX_23A0E66BCF5E72D ON article');
        $this->addSql('ALTER TABLE article MODIFY article_id INT NOT NULL');
        $this->addSql('ALTER TABLE article ADD id_article INT NOT NULL, ADD id_etudiant INT NOT NULL, ADD id_categorie INT NOT NULL, DROP article_id, DROP etudiant_id, DROP categorie_id, DROP PRIMARY KEY, ADD PRIMARY KEY (id_article)');
        $this->addSql('ALTER TABLE atelier MODIFY atelier_id INT NOT NULL');
        $this->addSql('ALTER TABLE atelier CHANGE atelier_id id_atelier INT AUTO_INCREMENT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id_atelier)');
        $this->addSql('ALTER TABLE candidature_job DROP FOREIGN KEY FK_E40B48E44CC8505A');
        $this->addSql('ALTER TABLE candidature_job MODIFY candidature_id INT NOT NULL');
        $this->addSql('ALTER TABLE candidature_job CHANGE candidature_id id_candidature INT AUTO_INCREMENT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id_candidature)');
        $this->addSql('ALTER TABLE candidature_job ADD CONSTRAINT `FK_E40B48E44CC8505A` FOREIGN KEY (offre_id) REFERENCES offrejob (id_offre) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE categorie MODIFY categorie_id INT NOT NULL');
        $this->addSql('ALTER TABLE categorie CHANGE categorie_id id_categorie INT AUTO_INCREMENT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id_categorie)');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DA76ED395');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67D7294869C');
        $this->addSql('DROP INDEX IDX_6EEAA67DA76ED395 ON commande');
        $this->addSql('DROP INDEX IDX_6EEAA67D7294869C ON commande');
        $this->addSql('DROP INDEX uniq_commande_session_article ON commande');
        $this->addSql('ALTER TABLE commande MODIFY commande_id INT NOT NULL');
        $this->addSql('ALTER TABLE commande CHANGE commande_id id_commande INT AUTO_INCREMENT NOT NULL, CHANGE user_id id_user INT DEFAULT NULL, CHANGE article_id id_article INT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id_commande)');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT `FK_6EEAA67A7294869C` FOREIGN KEY (id_article) REFERENCES article (id_article) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT `FK_6EEAA67D6B3CA4B` FOREIGN KEY (id_user) REFERENCES user (ID) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_6EEAA67DDCA7A716 ON commande (id_article)');
        $this->addSql('CREATE INDEX IDX_6EEAA67D6B3CA4B ON commande (id_user)');
        $this->addSql('CREATE UNIQUE INDEX uniq_commande_session_article ON commande (stripe_session_id, id_article)');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BC60BB6FE6');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BC83FDE077');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BC727ACA70');
        $this->addSql('DROP INDEX IDX_67F068BC60BB6FE6 ON commentaire');
        $this->addSql('DROP INDEX IDX_67F068BC83FDE077 ON commentaire');
        $this->addSql('DROP INDEX IDX_67F068BC727ACA70 ON commentaire');
        $this->addSql('ALTER TABLE commentaire MODIFY comm_id INT NOT NULL');
        $this->addSql('ALTER TABLE commentaire ADD id_auteur INT NOT NULL, ADD id_pub INT NOT NULL, DROP auteur_id, DROP pub_id, CHANGE comm_id id_comm INT AUTO_INCREMENT NOT NULL, CHANGE parent_id id_parent INT DEFAULT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id_comm)');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT `FK_67F068BC1BB9D5A2` FOREIGN KEY (id_parent) REFERENCES commentaire (id_comm) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT `FK_67F068BC236D04AD` FOREIGN KEY (id_auteur) REFERENCES user (ID)');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT `FK_67F068BCC4E0D4DF` FOREIGN KEY (id_pub) REFERENCES publication (id_pub)');
        $this->addSql('CREATE INDEX IDX_67F068BCC4E0D4DF ON commentaire (id_pub)');
        $this->addSql('CREATE INDEX IDX_67F068BC1BB9D5A2 ON commentaire (id_parent)');
        $this->addSql('CREATE INDEX IDX_67F068BC236D04AD ON commentaire (id_auteur)');
        $this->addSql('ALTER TABLE condidature_job DROP FOREIGN KEY FK_2F901D644CC8505A');
        $this->addSql('ALTER TABLE condidature_job MODIFY condidature_id INT NOT NULL');
        $this->addSql('ALTER TABLE condidature_job CHANGE condidature_id id_condidature INT AUTO_INCREMENT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id_condidature)');
        $this->addSql('ALTER TABLE condidature_job ADD CONSTRAINT `FK_2F901D644CC8505A` FOREIGN KEY (offre_id) REFERENCES offrejob (id_offre)');
        $this->addSql('ALTER TABLE historique_vue DROP FOREIGN KEY FK_27CBE987A76ED395');
        $this->addSql('ALTER TABLE historique_vue DROP FOREIGN KEY FK_27CBE9877294869C');
        $this->addSql('DROP INDEX IDX_27CBE987A76ED395 ON historique_vue');
        $this->addSql('DROP INDEX IDX_27CBE9877294869C ON historique_vue');
        $this->addSql('ALTER TABLE historique_vue MODIFY vue_id INT NOT NULL');
        $this->addSql('ALTER TABLE historique_vue ADD id_user INT NOT NULL, ADD id_article INT NOT NULL, DROP user_id, DROP article_id, CHANGE vue_id id_vue INT AUTO_INCREMENT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id_vue)');
        $this->addSql('ALTER TABLE historique_vue ADD CONSTRAINT `FK_94C9E0E56B3CA4B` FOREIGN KEY (id_user) REFERENCES user (ID) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE historique_vue ADD CONSTRAINT `FK_94C9E0E5A7294869C` FOREIGN KEY (id_article) REFERENCES article (id_article) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_27CBE987DCA7A716 ON historique_vue (id_article)');
        $this->addSql('CREATE INDEX IDX_27CBE9876B3CA4B ON historique_vue (id_user)');
        $this->addSql('ALTER TABLE learning_path DROP FOREIGN KEY FK_4D04C7975585C142');
        $this->addSql('DROP INDEX IDX_4D04C7975585C142 ON learning_path');
        $this->addSql('ALTER TABLE learning_path MODIFY learning_id INT NOT NULL');
        $this->addSql('ALTER TABLE learning_path CHANGE learning_id id_learning INT AUTO_INCREMENT NOT NULL, CHANGE skill_id id_skill INT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id_learning)');
        $this->addSql('ALTER TABLE learning_path ADD CONSTRAINT `FK_4D04C797B0B8A547` FOREIGN KEY (id_skill) REFERENCES skill (id_skill)');
        $this->addSql('CREATE INDEX IDX_4D04C797B0B8A547 ON learning_path (id_skill)');
        $this->addSql('ALTER TABLE offrejob MODIFY offre_id INT NOT NULL');
        $this->addSql('ALTER TABLE offrejob CHANGE offre_id id_offre INT AUTO_INCREMENT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id_offre)');
        $this->addSql('ALTER TABLE publication DROP FOREIGN KEY FK_AF3C677960BB6FE6');
        $this->addSql('DROP INDEX IDX_AF3C677960BB6FE6 ON publication');
        $this->addSql('ALTER TABLE publication MODIFY pub_id INT NOT NULL');
        $this->addSql('ALTER TABLE publication CHANGE pub_id id_pub INT AUTO_INCREMENT NOT NULL, CHANGE auteur_id id_auteur INT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id_pub)');
        $this->addSql('ALTER TABLE publication ADD CONSTRAINT `FK_AF3C6779236D04AD` FOREIGN KEY (id_auteur) REFERENCES user (ID)');
        $this->addSql('CREATE INDEX IDX_AF3C6779236D04AD ON publication (id_auteur)');
        $this->addSql('ALTER TABLE publication_reaction DROP FOREIGN KEY FK_8BB0F2A683FDE077');
        $this->addSql('ALTER TABLE publication_reaction DROP FOREIGN KEY FK_8BB0F2A6A76ED395');
        $this->addSql('DROP INDEX IDX_8BB0F2A683FDE077 ON publication_reaction');
        $this->addSql('DROP INDEX IDX_8BB0F2A6A76ED395 ON publication_reaction');
        $this->addSql('DROP INDEX uniq_pub_user_reaction ON publication_reaction');
        $this->addSql('ALTER TABLE publication_reaction MODIFY reaction_id INT NOT NULL');
        $this->addSql('ALTER TABLE publication_reaction ADD id_pub INT NOT NULL, ADD id_user INT NOT NULL, DROP pub_id, DROP user_id, CHANGE reaction_id id_reaction INT AUTO_INCREMENT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id_reaction)');
        $this->addSql('ALTER TABLE publication_reaction ADD CONSTRAINT `FK_529ACDCF6B3CA4B` FOREIGN KEY (id_user) REFERENCES user (ID) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE publication_reaction ADD CONSTRAINT `FK_529ACDCFC4E0D4DF` FOREIGN KEY (id_pub) REFERENCES publication (id_pub) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_8BB0F2A66B3CA4B ON publication_reaction (id_user)');
        $this->addSql('CREATE INDEX IDX_8BB0F2A6C4E0D4DF ON publication_reaction (id_pub)');
        $this->addSql('CREATE UNIQUE INDEX uniq_pub_user_reaction ON publication_reaction (id_pub, id_user)');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955A76ED395');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C8495582E2CF35');
        $this->addSql('DROP INDEX IDX_42C84955A76ED395 ON reservation');
        $this->addSql('DROP INDEX IDX_42C8495582E2CF35 ON reservation');
        $this->addSql('ALTER TABLE reservation MODIFY reservation_id INT NOT NULL');
        $this->addSql('ALTER TABLE reservation ADD id_user INT NOT NULL, ADD id_atelier INT NOT NULL, DROP user_id, DROP atelier_id, CHANGE reservation_id id_reservation INT AUTO_INCREMENT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id_reservation)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT `FK_42C849553F26B153` FOREIGN KEY (id_atelier) REFERENCES atelier (id_atelier)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT `FK_42C849556B3CA4B` FOREIGN KEY (id_user) REFERENCES user (ID)');
        $this->addSql('CREATE INDEX IDX_42C849553F26B153 ON reservation (id_atelier)');
        $this->addSql('CREATE INDEX IDX_42C849556B3CA4B ON reservation (id_user)');
        $this->addSql('ALTER TABLE skill MODIFY skill_id INT NOT NULL');
        $this->addSql('ALTER TABLE skill CHANGE skill_id id_skill INT AUTO_INCREMENT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id_skill)');
    }
}
