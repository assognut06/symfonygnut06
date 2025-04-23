<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250410221141 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE casque (id INT AUTO_INCREMENT NOT NULL, don_id INT DEFAULT NULL, marque_id INT NOT NULL, nom VARCHAR(70) NOT NULL, etat VARCHAR(50) NOT NULL, date_creation DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', date_mise_a_jour DATETIME NOT NULL, INDEX IDX_D8D997DB7B3C9061 (don_id), INDEX IDX_D8D997DB4827B9B2 (marque_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE don (id INT AUTO_INCREMENT NOT NULL, donateur_id INT NOT NULL, mode_livraison_id INT NOT NULL, date_creation DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', date_mise_a_jour DATETIME NOT NULL, statut VARCHAR(30) NOT NULL, message VARCHAR(500) DEFAULT NULL, numero_suivi VARCHAR(50) DEFAULT NULL, INDEX IDX_F8F081D9A9C80E3 (donateur_id), INDEX IDX_F8F081D9458F1D6 (mode_livraison_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE donateur (id INT AUTO_INCREMENT NOT NULL, civilite VARCHAR(15) NOT NULL, nom VARCHAR(70) NOT NULL, prenom VARCHAR(70) NOT NULL, email VARCHAR(255) NOT NULL, telephone VARCHAR(20) NOT NULL, adresse_1 VARCHAR(300) NOT NULL, adresse_2 VARCHAR(300) DEFAULT NULL, code_postale VARCHAR(10) NOT NULL, ville VARCHAR(150) NOT NULL, pays VARCHAR(150) NOT NULL, date_creation DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', date_mise_a_jour DATETIME NOT NULL, type_donateur VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE marque (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE mode_livraison (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, cout DOUBLE PRECISION NOT NULL, date_creation DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', date_mise_a_jour DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE mode_livraison_partenaire_logistique (mode_livraison_id INT NOT NULL, partenaire_logistique_id INT NOT NULL, INDEX IDX_D0A51E28458F1D6 (mode_livraison_id), INDEX IDX_D0A51E28DFD7EEFF (partenaire_logistique_id), PRIMARY KEY(mode_livraison_id, partenaire_logistique_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE partenaire_logistique (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, email VARCHAR(255) NOT NULL, telephone VARCHAR(20) NOT NULL, date_creation DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', date_mise_a_jour DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE personne_physique (id INT NOT NULL, date_naissance DATE DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE societe (id INT NOT NULL, nom_societe VARCHAR(200) DEFAULT NULL, siren VARCHAR(9) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE casque ADD CONSTRAINT FK_D8D997DB7B3C9061 FOREIGN KEY (don_id) REFERENCES don (id)');
        $this->addSql('ALTER TABLE casque ADD CONSTRAINT FK_D8D997DB4827B9B2 FOREIGN KEY (marque_id) REFERENCES marque (id)');
        $this->addSql('ALTER TABLE don ADD CONSTRAINT FK_F8F081D9A9C80E3 FOREIGN KEY (donateur_id) REFERENCES donateur (id)');
        $this->addSql('ALTER TABLE don ADD CONSTRAINT FK_F8F081D9458F1D6 FOREIGN KEY (mode_livraison_id) REFERENCES mode_livraison (id)');
        $this->addSql('ALTER TABLE mode_livraison_partenaire_logistique ADD CONSTRAINT FK_D0A51E28458F1D6 FOREIGN KEY (mode_livraison_id) REFERENCES mode_livraison (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE mode_livraison_partenaire_logistique ADD CONSTRAINT FK_D0A51E28DFD7EEFF FOREIGN KEY (partenaire_logistique_id) REFERENCES partenaire_logistique (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE personne_physique ADD CONSTRAINT FK_5C2B29A2BF396750 FOREIGN KEY (id) REFERENCES donateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE societe ADD CONSTRAINT FK_19653DBDBF396750 FOREIGN KEY (id) REFERENCES donateur (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE casque DROP FOREIGN KEY FK_D8D997DB7B3C9061');
        $this->addSql('ALTER TABLE casque DROP FOREIGN KEY FK_D8D997DB4827B9B2');
        $this->addSql('ALTER TABLE don DROP FOREIGN KEY FK_F8F081D9A9C80E3');
        $this->addSql('ALTER TABLE don DROP FOREIGN KEY FK_F8F081D9458F1D6');
        $this->addSql('ALTER TABLE mode_livraison_partenaire_logistique DROP FOREIGN KEY FK_D0A51E28458F1D6');
        $this->addSql('ALTER TABLE mode_livraison_partenaire_logistique DROP FOREIGN KEY FK_D0A51E28DFD7EEFF');
        $this->addSql('ALTER TABLE personne_physique DROP FOREIGN KEY FK_5C2B29A2BF396750');
        $this->addSql('ALTER TABLE societe DROP FOREIGN KEY FK_19653DBDBF396750');
        $this->addSql('DROP TABLE casque');
        $this->addSql('DROP TABLE don');
        $this->addSql('DROP TABLE donateur');
        $this->addSql('DROP TABLE marque');
        $this->addSql('DROP TABLE mode_livraison');
        $this->addSql('DROP TABLE mode_livraison_partenaire_logistique');
        $this->addSql('DROP TABLE partenaire_logistique');
        $this->addSql('DROP TABLE personne_physique');
        $this->addSql('DROP TABLE societe');
    }
}
