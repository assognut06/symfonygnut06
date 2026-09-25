<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260922160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Transforme les refus TIH en journal de tous les événements de candidature.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tih_rejection DROP FOREIGN KEY FK_98809D3BD2C13886');
        $this->addSql('ALTER TABLE tih_rejection DROP FOREIGN KEY FK_98809D3B474CB70C');
        $this->addSql('RENAME TABLE tih_rejection TO tih_application_event');
        $this->addSql("ALTER TABLE tih_application_event CHANGE rejected_by_id actor_id INT DEFAULT NULL, CHANGE reason reason LONGTEXT DEFAULT NULL, CHANGE rejected_at occurred_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', CHANGE email_status email_status VARCHAR(20) DEFAULT NULL, ADD status VARCHAR(20) DEFAULT 'refused' NOT NULL AFTER actor_id, ADD source VARCHAR(50) DEFAULT NULL AFTER occurred_at");
        $this->addSql('ALTER TABLE tih_application_event ADD CONSTRAINT FK_TIH_EVENT_TIH FOREIGN KEY (tih_id) REFERENCES tih (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tih_application_event ADD CONSTRAINT FK_TIH_EVENT_ACTOR FOREIGN KEY (actor_id) REFERENCES user (id)');
        $this->addSql("UPDATE tih_application_event SET status = 'refused', source = CASE WHEN actor_id IS NULL THEN 'legacy_migration' ELSE 'admin_decision' END");
        $this->addSql("INSERT INTO tih_application_event (tih_id, actor_id, status, reason, occurred_at, source, email_status, email_sent_at, email_error) SELECT id, user_id, application_status, NULL, updated_at, 'legacy_migration', NULL, NULL, NULL FROM tih WHERE application_status IN ('pending', 'approved')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM tih_application_event WHERE status <> 'refused'");
        $this->addSql('ALTER TABLE tih_application_event DROP FOREIGN KEY FK_TIH_EVENT_TIH');
        $this->addSql('ALTER TABLE tih_application_event DROP FOREIGN KEY FK_TIH_EVENT_ACTOR');
        $this->addSql("ALTER TABLE tih_application_event DROP status, DROP source, CHANGE actor_id rejected_by_id INT DEFAULT NULL, CHANGE reason reason LONGTEXT NOT NULL, CHANGE occurred_at rejected_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', CHANGE email_status email_status VARCHAR(20) NOT NULL");
        $this->addSql('RENAME TABLE tih_application_event TO tih_rejection');
        $this->addSql('ALTER TABLE tih_rejection ADD CONSTRAINT FK_98809D3BD2C13886 FOREIGN KEY (tih_id) REFERENCES tih (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tih_rejection ADD CONSTRAINT FK_98809D3B474CB70C FOREIGN KEY (rejected_by_id) REFERENCES user (id)');
    }
}
