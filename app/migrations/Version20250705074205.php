<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250705074205 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tih CHANGE cv cv VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user DROP is_tih');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tih CHANGE cv cv LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD is_tih TINYINT(1) NOT NULL');
    }
}
