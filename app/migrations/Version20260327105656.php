<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260327105656 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX tih_fulltext_search ON tih');
        $this->addSql('ALTER TABLE tih ADD region VARCHAR(100) DEFAULT NULL, ADD departement VARCHAR(100) DEFAULT NULL, ADD availability_date DATE DEFAULT NULL, ADD rate NUMERIC(10, 2) DEFAULT NULL, ADD rate_type VARCHAR(50) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tih DROP region, DROP departement, DROP availability_date, DROP rate, DROP rate_type');
        $this->addSql('CREATE FULLTEXT INDEX tih_fulltext_search ON tih (last_name, first_name, city, address, professional_email)');
    }
}
