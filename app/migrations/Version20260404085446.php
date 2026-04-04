<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260404085446 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE competence (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE entreprise_tih_message (id INT AUTO_INCREMENT NOT NULL, entreprise_id INT NOT NULL, tih_id INT NOT NULL, message LONGTEXT NOT NULL, date_candidature DATETIME NOT NULL, INDEX IDX_7F953E9BA4AEAFEA (entreprise_id), INDEX IDX_7F953E9BD2C13886 (tih_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tih (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, title VARCHAR(255) DEFAULT NULL, last_name VARCHAR(255) DEFAULT NULL, first_name VARCHAR(255) DEFAULT NULL, professional_email VARCHAR(255) DEFAULT NULL, phone VARCHAR(255) DEFAULT NULL, address VARCHAR(255) DEFAULT NULL, postal_code VARCHAR(10) DEFAULT NULL, city VARCHAR(255) DEFAULT NULL, region VARCHAR(100) DEFAULT NULL, departement VARCHAR(100) DEFAULT NULL, availability LONGTEXT DEFAULT NULL, availability_date DATE DEFAULT NULL, rate NUMERIC(10, 2) DEFAULT NULL, rate_type VARCHAR(50) DEFAULT NULL, cv VARCHAR(255) DEFAULT NULL, siret VARCHAR(255) DEFAULT NULL, attestation_tih VARCHAR(255) DEFAULT NULL, photo VARCHAR(255) DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, is_validate TINYINT(1) NOT NULL, validation_message LONGTEXT DEFAULT NULL, UNIQUE INDEX UNIQ_5BEF201AA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tih_competence (tih_id INT NOT NULL, competence_id INT NOT NULL, INDEX IDX_1958A632D2C13886 (tih_id), INDEX IDX_1958A63215761DAB (competence_id), PRIMARY KEY(tih_id, competence_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE entreprise_tih_message ADD CONSTRAINT FK_7F953E9BA4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES entreprise (id)');
        $this->addSql('ALTER TABLE entreprise_tih_message ADD CONSTRAINT FK_7F953E9BD2C13886 FOREIGN KEY (tih_id) REFERENCES tih (id)');
        $this->addSql('ALTER TABLE tih ADD CONSTRAINT FK_5BEF201AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE tih_competence ADD CONSTRAINT FK_1958A632D2C13886 FOREIGN KEY (tih_id) REFERENCES tih (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tih_competence ADD CONSTRAINT FK_1958A63215761DAB FOREIGN KEY (competence_id) REFERENCES competence (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE payers ADD phone VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, ADD updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE entreprise_tih_message DROP FOREIGN KEY FK_7F953E9BA4AEAFEA');
        $this->addSql('ALTER TABLE entreprise_tih_message DROP FOREIGN KEY FK_7F953E9BD2C13886');
        $this->addSql('ALTER TABLE tih DROP FOREIGN KEY FK_5BEF201AA76ED395');
        $this->addSql('ALTER TABLE tih_competence DROP FOREIGN KEY FK_1958A632D2C13886');
        $this->addSql('ALTER TABLE tih_competence DROP FOREIGN KEY FK_1958A63215761DAB');
        $this->addSql('DROP TABLE competence');
        $this->addSql('DROP TABLE entreprise_tih_message');
        $this->addSql('DROP TABLE tih');
        $this->addSql('DROP TABLE tih_competence');
        $this->addSql('ALTER TABLE payers DROP phone');
        $this->addSql('ALTER TABLE user DROP created_at, DROP updated_at');
    }
}
