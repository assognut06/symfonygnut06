<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261120160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Distingue les profils TIH réels des profils de démonstration.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tih ADD profile_kind VARCHAR(10) DEFAULT NULL');
        $this->addSql("UPDATE tih SET profile_kind = 'demo'");
        $this->addSql("ALTER TABLE tih MODIFY profile_kind VARCHAR(10) DEFAULT 'real' NOT NULL");
        $this->addSql('CREATE INDEX IDX_TIH_PROFILE_VISIBILITY ON tih (profile_kind, is_validate)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_TIH_PROFILE_VISIBILITY ON tih');
        $this->addSql('ALTER TABLE tih DROP profile_kind');
    }
}
