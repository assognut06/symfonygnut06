<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251226230015 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename Tih entity properties from French to English';
    }

    public function up(Schema $schema): void
    {
        
        // Add new columns
        $this->addSql('ALTER TABLE tih 
            ADD title VARCHAR(255) DEFAULT NULL, 
            ADD last_name VARCHAR(255) DEFAULT NULL, 
            ADD first_name VARCHAR(255) DEFAULT NULL, 
            ADD professional_email VARCHAR(255) DEFAULT NULL, 
            ADD phone VARCHAR(255) DEFAULT NULL, 
            ADD address VARCHAR(255) DEFAULT NULL, 
            ADD postal_code VARCHAR(10) DEFAULT NULL,
            ADD city VARCHAR(255) DEFAULT NULL,
            ADD availability LONGTEXT DEFAULT NULL');
        
        
        // Drop old columns
        $this->addSql('ALTER TABLE tih 
            DROP civilite, 
            DROP nom, 
            DROP prenom, 
            DROP email_pro, 
            DROP telephone, 
            DROP adresse, 
            DROP code_postal,
            DROP ville, 
            DROP disponibilite');
        
        // Create new fulltext index with English column names
        $this->addSql('CREATE FULLTEXT INDEX tih_fulltext_search ON tih (last_name, first_name, city, address, professional_email)');
    }

    public function down(Schema $schema): void
    {
        // Drop the fulltext index
        $this->addSql('DROP INDEX tih_fulltext_search ON tih');
        
        // Add old columns back
        $this->addSql('ALTER TABLE tih 
            ADD civilite VARCHAR(255) DEFAULT NULL, 
            ADD nom VARCHAR(255) DEFAULT NULL, 
            ADD prenom VARCHAR(255) DEFAULT NULL, 
            ADD email_pro VARCHAR(255) DEFAULT NULL, 
            ADD telephone VARCHAR(255) DEFAULT NULL, 
            ADD adresse VARCHAR(255) DEFAULT NULL, 
            ADD code_postal VARCHAR(10) DEFAULT NULL,
            ADD ville VARCHAR(255) DEFAULT NULL,
            ADD disponibilite LONGTEXT DEFAULT NULL');
        
        // Copy data back
        $this->addSql('UPDATE tih SET 
            civilite = title,
            nom = last_name,
            prenom = first_name,
            email_pro = professional_email,
            telephone = phone,
            adresse = address,
            code_postal = postal_code,
            ville = city,
            disponibilite = availability');
        
        // Drop new columns
        $this->addSql('ALTER TABLE tih 
            DROP title, 
            DROP last_name, 
            DROP first_name, 
            DROP professional_email, 
            DROP phone, 
            DROP address, 
            DROP postal_code,
            DROP city,
            DROP availability');
        
        // Recreate old fulltext index
        $this->addSql('CREATE FULLTEXT INDEX tih_fulltext_search ON tih (nom, prenom, ville, adresse, email_pro)');
    }
}
