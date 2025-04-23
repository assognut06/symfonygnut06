<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250417130852 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE mode_livraison_partenaire_logistique DROP FOREIGN KEY FK_D0A51E28458F1D6');
        $this->addSql('ALTER TABLE mode_livraison_partenaire_logistique DROP FOREIGN KEY FK_D0A51E28DFD7EEFF');
        $this->addSql('DROP TABLE mode_livraison_partenaire_logistique');
        $this->addSql('ALTER TABLE asso_recommander CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE don ADD partenaire_logistique_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE don ADD CONSTRAINT FK_F8F081D9DFD7EEFF FOREIGN KEY (partenaire_logistique_id) REFERENCES partenaire_logistique (id)');
        $this->addSql('CREATE INDEX IDX_F8F081D9DFD7EEFF ON don (partenaire_logistique_id)');
        $this->addSql('ALTER TABLE donateur CHANGE code_postale code_postal VARCHAR(10) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE mode_livraison_partenaire_logistique (mode_livraison_id INT NOT NULL, partenaire_logistique_id INT NOT NULL, INDEX IDX_D0A51E28DFD7EEFF (partenaire_logistique_id), INDEX IDX_D0A51E28458F1D6 (mode_livraison_id), PRIMARY KEY(mode_livraison_id, partenaire_logistique_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE mode_livraison_partenaire_logistique ADD CONSTRAINT FK_D0A51E28458F1D6 FOREIGN KEY (mode_livraison_id) REFERENCES mode_livraison (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE mode_livraison_partenaire_logistique ADD CONSTRAINT FK_D0A51E28DFD7EEFF FOREIGN KEY (partenaire_logistique_id) REFERENCES partenaire_logistique (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE asso_recommander CHANGE id id INT NOT NULL');
        $this->addSql('ALTER TABLE don DROP FOREIGN KEY FK_F8F081D9DFD7EEFF');
        $this->addSql('DROP INDEX IDX_F8F081D9DFD7EEFF ON don');
        $this->addSql('ALTER TABLE don DROP partenaire_logistique_id');
        $this->addSql('ALTER TABLE donateur CHANGE code_postal code_postale VARCHAR(10) NOT NULL');
    }
}
