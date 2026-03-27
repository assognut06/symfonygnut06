<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251225234253 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add photo field to Tih entity and create FULLTEXT indexes for search optimization';
    }

    public function up(Schema $schema): void
    {
        // Add photo field
        $this->addSql('ALTER TABLE tih ADD photo VARCHAR(255) DEFAULT NULL');
        
    }

    public function down(Schema $schema): void
    {

        // Drop photo field
        $this->addSql('ALTER TABLE tih DROP photo');
    }
}
