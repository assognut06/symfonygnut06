<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251118201854 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create tables with optimized indexes to avoid key length issues';
    }

    public function up(Schema $schema): void
    {
        // Les tables HelloAsso existent déjà, on les ignore
        // Créer ou modifier uniquement asso_recommander si nécessaire
        
        // Vérifier si la table existe déjà et la créer seulement si nécessaire
        $tableExists = $this->connection->executeQuery("SHOW TABLES LIKE 'asso_recommander'")->fetchOne();
        
        if (!$tableExists) {
            $this->addSql('CREATE TABLE asso_recommander (
                id INT AUTO_INCREMENT NOT NULL, 
                organization_slug VARCHAR(191) NOT NULL, 
                banner VARCHAR(500) DEFAULT NULL, 
                fiscal_receipt_eligibility TINYINT(1) DEFAULT NULL, 
                fiscal_receipt_issuance_enabled TINYINT(1) DEFAULT NULL, 
                type VARCHAR(100) DEFAULT NULL, 
                category VARCHAR(100) DEFAULT NULL, 
                logo VARCHAR(500) DEFAULT NULL, 
                name VARCHAR(191) DEFAULT NULL, 
                city VARCHAR(100) DEFAULT NULL, 
                zip_code VARCHAR(10) DEFAULT NULL, 
                description LONGTEXT DEFAULT NULL, 
                url VARCHAR(500) DEFAULT NULL, 
                created_at DATETIME NOT NULL COMMENT "(DC2Type:datetime_immutable)", 
                updated_at DATETIME DEFAULT NULL, 
                meta_created_at DATETIME DEFAULT NULL COMMENT "(DC2Type:datetime_immutable)", 
                meta_updated_at DATETIME DEFAULT NULL COMMENT "(DC2Type:datetime_immutable)", 
                meta_created_by VARCHAR(255) DEFAULT NULL, 
                meta_updated_by VARCHAR(255) DEFAULT NULL, 
                PRIMARY KEY(id),
                UNIQUE INDEX unique_organization_slug (organization_slug),
                INDEX idx_name (name(100)),
                INDEX idx_city (city),
                INDEX idx_zip_code (zip_code),
                INDEX idx_category (category),
                INDEX idx_type (type)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        } else {
            // Si la table existe, ajouter les colonnes manquantes seulement
            $columns = $this->connection->executeQuery("DESCRIBE asso_recommander")->fetchAllAssociative();
            $columnNames = array_column($columns, 'Field');
            
            if (!in_array('meta_created_at', $columnNames)) {
                $this->addSql('ALTER TABLE asso_recommander ADD meta_created_at DATETIME DEFAULT NULL COMMENT "(DC2Type:datetime_immutable)"');
            }
            if (!in_array('meta_updated_at', $columnNames)) {
                $this->addSql('ALTER TABLE asso_recommander ADD meta_updated_at DATETIME DEFAULT NULL COMMENT "(DC2Type:datetime_immutable)"');
            }
            if (!in_array('meta_created_by', $columnNames)) {
                $this->addSql('ALTER TABLE asso_recommander ADD meta_created_by VARCHAR(255) DEFAULT NULL');
            }
            if (!in_array('meta_updated_by', $columnNames)) {
                $this->addSql('ALTER TABLE asso_recommander ADD meta_updated_by VARCHAR(255) DEFAULT NULL');
            }
            
            // Modifier les colonnes existantes si nécessaire
            $this->addSql('ALTER TABLE asso_recommander MODIFY COLUMN organization_slug VARCHAR(191) NOT NULL');
            $this->addSql('ALTER TABLE asso_recommander MODIFY COLUMN name VARCHAR(191) DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE helloasso_tier DROP FOREIGN KEY FK_D8E59F3F5FF69B7D');
        $this->addSql('ALTER TABLE helloasso_tier_custom_field DROP FOREIGN KEY FK_E6453AB7A354F9DC');
        $this->addSql('DROP TABLE helloasso_form_notification');
        $this->addSql('DROP TABLE helloasso_tier');
        $this->addSql('DROP TABLE helloasso_tier_custom_field');
        $this->addSql('DROP TABLE IF EXISTS asso_recommander');
    }
}