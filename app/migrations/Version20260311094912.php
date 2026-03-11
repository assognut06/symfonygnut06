<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260311094912 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE asso_recommander (id INT AUTO_INCREMENT NOT NULL, organization_slug VARCHAR(255) NOT NULL, banner VARCHAR(255) DEFAULT NULL, fiscal_receipt_eligibility TINYINT(1) DEFAULT NULL, fiscal_receipt_issuance_enabled TINYINT(1) DEFAULT NULL, type VARCHAR(255) DEFAULT NULL, category VARCHAR(255) DEFAULT NULL, logo VARCHAR(255) DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, city VARCHAR(255) DEFAULT NULL, zip_code VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, url VARCHAR(255) DEFAULT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE benevole (id INT AUTO_INCREMENT NOT NULL, civilite VARCHAR(15) NOT NULL, nom VARCHAR(70) NOT NULL, prenom VARCHAR(70) NOT NULL, email VARCHAR(180) NOT NULL, email_pro VARCHAR(150) DEFAULT NULL, telephone VARCHAR(20) NOT NULL, adresse_1 VARCHAR(300) DEFAULT NULL, adresse_2 VARCHAR(300) DEFAULT NULL, code_postal VARCHAR(10) DEFAULT NULL, ville VARCHAR(150) DEFAULT NULL, pays VARCHAR(150) DEFAULT NULL, date_creation DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', date_mise_a_jour DATETIME NOT NULL, type VARCHAR(70) NOT NULL, asso_trouve_par VARCHAR(100) DEFAULT NULL, cv VARCHAR(255) DEFAULT NULL, commentaire VARCHAR(500) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE casque (id INT AUTO_INCREMENT NOT NULL, marque_id INT NOT NULL, don_id INT NOT NULL, etat VARCHAR(50) NOT NULL, date_creation DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', date_mise_a_jour DATETIME NOT NULL, INDEX IDX_D8D997DB4827B9B2 (marque_id), INDEX IDX_D8D997DB7B3C9061 (don_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE don (id INT AUTO_INCREMENT NOT NULL, donateur_id INT NOT NULL, mode_livraison_id INT NOT NULL, partenaire_logistique_id INT DEFAULT NULL, date_creation DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', date_mise_a_jour DATETIME NOT NULL, statut VARCHAR(30) NOT NULL, message VARCHAR(500) DEFAULT NULL, numero_suivi VARCHAR(50) DEFAULT NULL, bordereau VARCHAR(150) DEFAULT NULL, INDEX IDX_F8F081D9A9C80E3 (donateur_id), INDEX IDX_F8F081D9458F1D6 (mode_livraison_id), INDEX IDX_F8F081D9DFD7EEFF (partenaire_logistique_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE donateur (id INT AUTO_INCREMENT NOT NULL, civilite VARCHAR(15) NOT NULL, nom VARCHAR(70) NOT NULL, prenom VARCHAR(70) NOT NULL, email VARCHAR(180) NOT NULL, telephone VARCHAR(20) NOT NULL, adresse_1 VARCHAR(300) NOT NULL, adresse_2 VARCHAR(300) DEFAULT NULL, code_postal VARCHAR(10) NOT NULL, ville VARCHAR(150) NOT NULL, pays VARCHAR(150) NOT NULL, date_creation DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', date_mise_a_jour DATETIME NOT NULL, type_donateur VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE entreprise (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, logo VARCHAR(255) NOT NULL, updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', url VARCHAR(200) NOT NULL, email VARCHAR(150) DEFAULT NULL, telephone VARCHAR(20) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE marque (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE mode_livraison (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, cout DOUBLE PRECISION NOT NULL, date_creation DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', date_mise_a_jour DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE partenaire_logistique (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, email VARCHAR(255) NOT NULL, telephone VARCHAR(20) NOT NULL, date_creation DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', date_mise_a_jour DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE payers (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(255) NOT NULL, address VARCHAR(255) DEFAULT NULL, city VARCHAR(255) DEFAULT NULL, zip_code VARCHAR(5) DEFAULT NULL, country VARCHAR(255) DEFAULT NULL, company VARCHAR(255) DEFAULT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE personne_physique (id INT NOT NULL, date_naissance DATE DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reset_password_request (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, selector VARCHAR(20) NOT NULL, hashed_token VARCHAR(100) NOT NULL, requested_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_7CE748AA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE societe (id INT NOT NULL, nom_societe VARCHAR(200) DEFAULT NULL, siren VARCHAR(9) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, is_verified TINYINT(1) NOT NULL, profile_picture VARCHAR(255) DEFAULT NULL, google_id VARCHAR(255) DEFAULT NULL, azure_id VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE casque ADD CONSTRAINT FK_D8D997DB4827B9B2 FOREIGN KEY (marque_id) REFERENCES marque (id)');
        $this->addSql('ALTER TABLE casque ADD CONSTRAINT FK_D8D997DB7B3C9061 FOREIGN KEY (don_id) REFERENCES don (id)');
        $this->addSql('ALTER TABLE don ADD CONSTRAINT FK_F8F081D9A9C80E3 FOREIGN KEY (donateur_id) REFERENCES donateur (id)');
        $this->addSql('ALTER TABLE don ADD CONSTRAINT FK_F8F081D9458F1D6 FOREIGN KEY (mode_livraison_id) REFERENCES mode_livraison (id)');
        $this->addSql('ALTER TABLE don ADD CONSTRAINT FK_F8F081D9DFD7EEFF FOREIGN KEY (partenaire_logistique_id) REFERENCES partenaire_logistique (id)');
        $this->addSql('ALTER TABLE personne_physique ADD CONSTRAINT FK_5C2B29A2BF396750 FOREIGN KEY (id) REFERENCES donateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE societe ADD CONSTRAINT FK_19653DBDBF396750 FOREIGN KEY (id) REFERENCES donateur (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE casque DROP FOREIGN KEY FK_D8D997DB4827B9B2');
        $this->addSql('ALTER TABLE casque DROP FOREIGN KEY FK_D8D997DB7B3C9061');
        $this->addSql('ALTER TABLE don DROP FOREIGN KEY FK_F8F081D9A9C80E3');
        $this->addSql('ALTER TABLE don DROP FOREIGN KEY FK_F8F081D9458F1D6');
        $this->addSql('ALTER TABLE don DROP FOREIGN KEY FK_F8F081D9DFD7EEFF');
        $this->addSql('ALTER TABLE personne_physique DROP FOREIGN KEY FK_5C2B29A2BF396750');
        $this->addSql('ALTER TABLE reset_password_request DROP FOREIGN KEY FK_7CE748AA76ED395');
        $this->addSql('ALTER TABLE societe DROP FOREIGN KEY FK_19653DBDBF396750');
        $this->addSql('DROP TABLE asso_recommander');
        $this->addSql('DROP TABLE benevole');
        $this->addSql('DROP TABLE casque');
        $this->addSql('DROP TABLE don');
        $this->addSql('DROP TABLE donateur');
        $this->addSql('DROP TABLE entreprise');
        $this->addSql('DROP TABLE marque');
        $this->addSql('DROP TABLE mode_livraison');
        $this->addSql('DROP TABLE partenaire_logistique');
        $this->addSql('DROP TABLE payers');
        $this->addSql('DROP TABLE personne_physique');
        $this->addSql('DROP TABLE reset_password_request');
        $this->addSql('DROP TABLE societe');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
