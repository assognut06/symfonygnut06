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
        // Créer les tables HelloAsso (celles-ci semblent OK)
        $this->addSql('CREATE TABLE helloasso_form_notification (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', event_type VARCHAR(50) NOT NULL, organization_logo VARCHAR(2048) DEFAULT NULL, organization_name VARCHAR(255) DEFAULT NULL, activity_type VARCHAR(100) DEFAULT NULL, activity_type_id INT DEFAULT NULL, currency VARCHAR(3) DEFAULT NULL, description LONGTEXT DEFAULT NULL, start_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', end_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', state VARCHAR(50) DEFAULT NULL, title VARCHAR(255) DEFAULT NULL, widget_button_url VARCHAR(2048) DEFAULT NULL, widget_full_url VARCHAR(2048) DEFAULT NULL, widget_vignette_horizontal_url VARCHAR(2048) DEFAULT NULL, widget_vignette_vertical_url VARCHAR(2048) DEFAULT NULL, form_slug VARCHAR(255) DEFAULT NULL, form_type VARCHAR(50) DEFAULT NULL, url VARCHAR(2048) DEFAULT NULL, organization_slug VARCHAR(150) DEFAULT NULL, place_address VARCHAR(255) DEFAULT NULL, place_name VARCHAR(255) DEFAULT NULL, place_city VARCHAR(120) DEFAULT NULL, place_zip_code VARCHAR(20) DEFAULT NULL, place_country VARCHAR(3) DEFAULT NULL, banner_file_name VARCHAR(255) DEFAULT NULL, banner_public_url VARCHAR(2048) DEFAULT NULL, logo_file_name VARCHAR(255) DEFAULT NULL, logo_public_url VARCHAR(2048) DEFAULT NULL, meta_created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', meta_updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', meta_created_by VARCHAR(255) DEFAULT NULL, meta_updated_by VARCHAR(255) DEFAULT NULL, INDEX idx_helloasso_form_start_date (start_date), INDEX idx_helloasso_form_org_slug (organization_slug), UNIQUE INDEX uniq_helloasso_form_slug (form_slug), UNIQUE INDEX uniq_helloasso_url (url), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        $this->addSql('CREATE TABLE helloasso_tier (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', form_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', external_id INT NOT NULL, label VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, tier_type VARCHAR(50) DEFAULT NULL, price NUMERIC(10, 2) NOT NULL, vat_rate NUMERIC(5, 2) NOT NULL, payment_frequency VARCHAR(50) DEFAULT NULL, is_eligible_tax_receipt TINYINT(1) DEFAULT NULL, is_favorite TINYINT(1) DEFAULT NULL, INDEX IDX_D8E59F3F5FF69B7D (form_id), INDEX idx_tier_external (external_id), UNIQUE INDEX uniq_tier_form_external (form_id, external_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        $this->addSql('CREATE TABLE helloasso_tier_custom_field (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', tier_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', external_id INT NOT NULL, label VARCHAR(255) DEFAULT NULL, type VARCHAR(50) DEFAULT NULL, is_required TINYINT(1) DEFAULT 0 NOT NULL, `values` JSON DEFAULT NULL, INDEX IDX_E6453AB7A354F9DC (tier_id), INDEX idx_cf_external (external_id), UNIQUE INDEX uniq_cf_tier_external (tier_id, external_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        // Contraintes FK pour HelloAsso
        $this->addSql('ALTER TABLE helloasso_tier ADD CONSTRAINT FK_D8E59F3F5FF69B7D FOREIGN KEY (form_id) REFERENCES helloasso_form_notification (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE helloasso_tier_custom_field ADD CONSTRAINT FK_E6453AB7A354F9DC FOREIGN KEY (tier_id) REFERENCES helloasso_tier (id) ON DELETE CASCADE');
        
        // ✅ MODIFICATION : Créer asso_recommander sans index problématiques d'abord
        $this->addSql('CREATE TABLE IF NOT EXISTS asso_recommander (
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
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        // ✅ MODIFICATION : Ajouter les index un par un avec gestion d'erreur
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS unique_organization_slug ON asso_recommander (organization_slug)');
        
        // Index simples - plus sûrs
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_name ON asso_recommander (name(100))'); // Index partiel
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_city ON asso_recommander (city)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_zip_code ON asso_recommander (zip_code)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_category ON asso_recommander (category)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_type ON asso_recommander (type)');
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