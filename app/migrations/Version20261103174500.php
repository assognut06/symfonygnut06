<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261103144500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création des tables mission et mission_competence';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE mission (
            id INT AUTO_INCREMENT NOT NULL,
            compte_entreprise_id INT NOT NULL,
            titre VARCHAR(120) DEFAULT NULL,
            description LONGTEXT DEFAULT NULL,
            domaine VARCHAR(120) DEFAULT NULL,
            zone VARCHAR(120) DEFAULT NULL,
            modalite VARCHAR(20) DEFAULT NULL,
            date_debut_souhaitee DATE DEFAULT NULL COMMENT '(DC2Type:date_immutable)',
            duree_estimee VARCHAR(100) DEFAULT NULL,
            date_limite_reponse DATE DEFAULT NULL COMMENT '(DC2Type:date_immutable)',
            budget_min NUMERIC(10, 2) DEFAULT NULL,
            budget_max NUMERIC(10, 2) DEFAULT NULL,
            unite_budget VARCHAR(20) DEFAULT NULL,
            statut VARCHAR(20) NOT NULL,
            created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
            updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
            submitted_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
            published_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
            INDEX IDX_9067F23C77147195 (compte_entreprise_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");

        $this->addSql("CREATE TABLE mission_competence (
            mission_id INT NOT NULL,
            competence_id INT NOT NULL,
            INDEX IDX_D6D445E49067F23C (mission_id),
            INDEX IDX_D6D445E415761DAB (competence_id),
            PRIMARY KEY(mission_id, competence_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");

        $this->addSql('ALTER TABLE mission ADD CONSTRAINT FK_9067F23C77147195 FOREIGN KEY (compte_entreprise_id) REFERENCES compte_entreprise (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE mission_competence ADD CONSTRAINT FK_D6D445E49067F23C FOREIGN KEY (mission_id) REFERENCES mission (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE mission_competence ADD CONSTRAINT FK_D6D445E415761DAB FOREIGN KEY (competence_id) REFERENCES competence (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mission_competence DROP FOREIGN KEY FK_D6D445E49067F23C');
        $this->addSql('ALTER TABLE mission_competence DROP FOREIGN KEY FK_D6D445E415761DAB');
        $this->addSql('DROP TABLE mission_competence');
        $this->addSql('ALTER TABLE mission DROP FOREIGN KEY FK_9067F23C77147195');
        $this->addSql('DROP TABLE mission');
    }
}
