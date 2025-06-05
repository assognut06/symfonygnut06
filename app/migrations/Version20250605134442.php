<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250605134442 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
{
    // Étape 1 : ajouter les colonnes en autorisant NULL temporairement
    $this->addSql('ALTER TABLE user ADD date_creation DATETIME DEFAULT NULL, ADD date_mise_a_jour DATETIME DEFAULT NULL');

    // Étape 2 : remplir les champs pour les utilisateurs existants
    $this->addSql('UPDATE user SET date_creation = NOW(), date_mise_a_jour = NOW() WHERE date_creation IS NULL OR date_mise_a_jour IS NULL');

    // Étape 3 : rendre les colonnes NOT NULL
    $this->addSql('ALTER TABLE user MODIFY date_creation DATETIME NOT NULL, MODIFY date_mise_a_jour DATETIME NOT NULL');
}


    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user DROP date_creation, DROP date_mise_a_jour');
    }
}
