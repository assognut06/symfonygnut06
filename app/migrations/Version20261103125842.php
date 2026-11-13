<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261103125842 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création de la table compte_entreprise liée aux utilisateurs';
    }

    public function up(Schema $schema): void
    {
        // Création de la table compte_entreprise
        $this->addSql("
            CREATE TABLE compte_entreprise (
                id INT AUTO_INCREMENT NOT NULL,
                user_id INT NOT NULL,
                raison_sociale VARCHAR(160) NOT NULL,
                identifiant_legal VARCHAR(14) DEFAULT NULL,
                nom_contact VARCHAR(150) NOT NULL,
                email_contact VARCHAR(180) NOT NULL,
                telephone VARCHAR(20) DEFAULT NULL,
                statut VARCHAR(20) NOT NULL,
                created_at DATETIME NOT NULL
                    COMMENT '(DC2Type:datetime_immutable)',
                updated_at DATETIME NOT NULL
                    COMMENT '(DC2Type:datetime_immutable)',
                UNIQUE INDEX UNIQ_C50D25DEA76ED395 (user_id),
                PRIMARY KEY(id)
            )
            DEFAULT CHARACTER SET utf8mb4
            COLLATE utf8mb4_unicode_ci
            ENGINE = InnoDB
        ");

        // Relation avec la table user existante
        $this->addSql('
            ALTER TABLE compte_entreprise
            ADD CONSTRAINT FK_C50D25DEA76ED395
            FOREIGN KEY (user_id) REFERENCES user (id)
        ');
    }

    public function down(Schema $schema): void
    {
        // Suppression de la relation
        $this->addSql('
            ALTER TABLE compte_entreprise
            DROP FOREIGN KEY FK_C50D25DEA76ED395
        ');

        // Suppression de la table
        $this->addSql('DROP TABLE compte_entreprise');
    }
}