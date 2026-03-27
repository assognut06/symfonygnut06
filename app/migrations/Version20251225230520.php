<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251225230520 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add phone field to Payers entity';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE payers ADD phone VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE payers DROP phone');
    }
}
