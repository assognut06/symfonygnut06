<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260912120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Harden OAuth identities with provider tenant identity and unique constraints.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD azure_tenant_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_USER_GOOGLE_ID ON user (google_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_USER_AZURE_IDENTITY ON user (azure_tenant_id, azure_id)');
        $this->addSql('CREATE TABLE microsoft_identity_repair (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, legacy_azure_id VARCHAR(255) NOT NULL, azure_tenant_id VARCHAR(255) NOT NULL, azure_object_id VARCHAR(255) NOT NULL, token_hash VARCHAR(64) NOT NULL, expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', consumed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_94F3804EA76ED395 (user_id), INDEX IDX_MICROSOFT_REPAIR_TOKEN (token_hash), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE microsoft_identity_repair ADD CONSTRAINT FK_MICROSOFT_REPAIR_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_USER_GOOGLE_ID ON user');
        $this->addSql('DROP INDEX UNIQ_USER_AZURE_IDENTITY ON user');
        $this->addSql('ALTER TABLE microsoft_identity_repair DROP FOREIGN KEY FK_MICROSOFT_REPAIR_USER');
        $this->addSql('DROP TABLE microsoft_identity_repair');
        $this->addSql('ALTER TABLE user DROP azure_tenant_id');
    }
}
