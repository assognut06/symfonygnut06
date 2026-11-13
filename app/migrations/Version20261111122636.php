<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261111122636 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create decision_moderation table for mission moderation history';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE decision_moderation (
                id INT AUTO_INCREMENT NOT NULL,
                mission_id INT NOT NULL,
                moderator_id INT NOT NULL,
                decision VARCHAR(30) NOT NULL,
                reason LONGTEXT DEFAULT NULL,
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                INDEX IDX_8AF42324BE6CAE90 (mission_id),
                INDEX IDX_8AF42324D0AFA354 (moderator_id),
                PRIMARY KEY(id)
            )
            DEFAULT CHARACTER SET utf8mb4
            COLLATE `utf8mb4_unicode_ci`
            ENGINE = InnoDB
        ");

        $this->addSql("
            ALTER TABLE decision_moderation
            ADD CONSTRAINT FK_8AF42324BE6CAE90
            FOREIGN KEY (mission_id)
            REFERENCES mission (id)
            ON DELETE RESTRICT
        ");

        $this->addSql("
            ALTER TABLE decision_moderation
            ADD CONSTRAINT FK_8AF42324D0AFA354
            FOREIGN KEY (moderator_id)
            REFERENCES user (id)
            ON DELETE RESTRICT
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE decision_moderation
            DROP FOREIGN KEY FK_8AF42324BE6CAE90
        ");

        $this->addSql("
            ALTER TABLE decision_moderation
            DROP FOREIGN KEY FK_8AF42324D0AFA354
        ");

        $this->addSql("
            DROP TABLE decision_moderation
        ");
    }
}