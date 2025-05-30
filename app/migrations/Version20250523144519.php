<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250523144519 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE benevole CHANGE adresse_1 adresse_1 VARCHAR(300) DEFAULT NULL, CHANGE code_postal code_postal VARCHAR(10) DEFAULT NULL, CHANGE ville ville VARCHAR(150) DEFAULT NULL, CHANGE pays pays VARCHAR(150) DEFAULT NULL, CHANGE asso_trouve_par asso_trouve_par VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE benevole CHANGE adresse_1 adresse_1 VARCHAR(300) NOT NULL, CHANGE code_postal code_postal VARCHAR(10) NOT NULL, CHANGE ville ville VARCHAR(150) NOT NULL, CHANGE pays pays VARCHAR(150) NOT NULL, CHANGE asso_trouve_par asso_trouve_par VARCHAR(100) NOT NULL');
    }
}
