<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260621120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add unique indexes for OAuth provider identifiers.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX UNIQ_USER_GOOGLE_ID ON user (google_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_USER_AZURE_ID ON user (azure_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_USER_GOOGLE_ID ON user');
        $this->addSql('DROP INDEX UNIQ_USER_AZURE_ID ON user');
    }
}
