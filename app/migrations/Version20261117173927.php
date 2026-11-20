<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261117173927 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD is_active TINYINT(1) DEFAULT 1 NOT NULL');
    }
    
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP is_active');
    }
}
