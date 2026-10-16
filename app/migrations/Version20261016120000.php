<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261016120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la procédure de suppression des comptes non vérifiés, sans rôle et âgés de plus de 15 jours.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reset_password_request DROP FOREIGN KEY FK_7CE748AA76ED395');
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tih DROP FOREIGN KEY FK_5BEF201AA76ED395');
        $this->addSql('ALTER TABLE tih ADD CONSTRAINT FK_5BEF201AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tih_application_event DROP FOREIGN KEY FK_TIH_EVENT_ACTOR');
        $this->addSql('ALTER TABLE tih_application_event ADD CONSTRAINT FK_TIH_EVENT_ACTOR FOREIGN KEY (actor_id) REFERENCES user (id) ON DELETE SET NULL');

        $this->addSql('DROP FUNCTION IF EXISTS delete_invalid_users');
        $this->addSql(<<<'SQL'
            CREATE PROCEDURE delete_invalid_users()
            MODIFIES SQL DATA
            BEGIN
                DELETE FROM user
                WHERE is_verified = 0
                  AND JSON_TYPE(roles) = 'ARRAY'
                  AND JSON_LENGTH(roles) = 0
                  AND created_at < DATE_SUB(NOW(), INTERVAL 15 DAY);

                SELECT ROW_COUNT() AS deleted_users;
            END
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP PROCEDURE IF EXISTS delete_invalid_users');

        $this->addSql('ALTER TABLE tih_application_event DROP FOREIGN KEY FK_TIH_EVENT_ACTOR');
        $this->addSql('ALTER TABLE tih_application_event ADD CONSTRAINT FK_TIH_EVENT_ACTOR FOREIGN KEY (actor_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE tih DROP FOREIGN KEY FK_5BEF201AA76ED395');
        $this->addSql('ALTER TABLE tih ADD CONSTRAINT FK_5BEF201AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE reset_password_request DROP FOREIGN KEY FK_7CE748AA76ED395');
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }
}
