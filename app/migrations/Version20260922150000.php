<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260922150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le statut et la traçabilité des refus de candidatures TIH.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE tih ADD application_status VARCHAR(20) DEFAULT 'pending' NOT NULL");
        $this->addSql("UPDATE tih SET application_status = CASE WHEN is_validate = 1 THEN 'approved' WHEN validation_message IS NOT NULL AND TRIM(validation_message) <> '' THEN 'refused' ELSE 'pending' END");
        $this->addSql('CREATE TABLE tih_rejection (id INT AUTO_INCREMENT NOT NULL, tih_id INT NOT NULL, rejected_by_id INT DEFAULT NULL, reason LONGTEXT NOT NULL, rejected_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', email_status VARCHAR(20) NOT NULL, email_sent_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', email_error LONGTEXT DEFAULT NULL, INDEX IDX_98809D3BD2C13886 (tih_id), INDEX IDX_98809D3B474CB70C (rejected_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tih_rejection ADD CONSTRAINT FK_98809D3BD2C13886 FOREIGN KEY (tih_id) REFERENCES tih (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tih_rejection ADD CONSTRAINT FK_98809D3B474CB70C FOREIGN KEY (rejected_by_id) REFERENCES user (id)');
        $this->addSql("INSERT INTO tih_rejection (tih_id, rejected_by_id, reason, rejected_at, email_status, email_sent_at, email_error) SELECT id, NULL, validation_message, updated_at, 'failed', NULL, 'Décision historique : notification non tracée.' FROM tih WHERE validation_message IS NOT NULL AND TRIM(validation_message) <> ''");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tih_rejection DROP FOREIGN KEY FK_98809D3BD2C13886');
        $this->addSql('ALTER TABLE tih_rejection DROP FOREIGN KEY FK_98809D3B474CB70C');
        $this->addSql('DROP TABLE tih_rejection');
        $this->addSql('ALTER TABLE tih DROP application_status');
    }
}
