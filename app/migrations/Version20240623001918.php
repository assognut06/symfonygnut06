<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240623001918 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE asso_recommander (id INT AUTO_INCREMENT NOT NULL, organization_slug VARCHAR(255) NOT NULL, banner VARCHAR(255) DEFAULT NULL, fiscal_receipt_eligibility TINYINT(1) DEFAULT NULL, fiscal_receipt_issuance_enabled TINYINT(1) DEFAULT NULL, type VARCHAR(255) DEFAULT NULL, category VARCHAR(255) DEFAULT NULL, logo VARCHAR(255) DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, city VARCHAR(255) DEFAULT NULL, zip_code VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, url VARCHAR(255) DEFAULT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE asso_recommander');
    }
}
