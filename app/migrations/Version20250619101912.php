<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250619101912 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE entreprise_tih_message DROP FOREIGN KEY fk_entreprise_tih_message_entreprise');
        $this->addSql('ALTER TABLE entreprise_tih_message DROP FOREIGN KEY fk_entreprise_tih_message_tih');
        $this->addSql('DROP INDEX fk_entreprise_tih_message_tih ON entreprise_tih_message');
        $this->addSql('DROP INDEX fk_entreprise_tih_message_entreprise ON entreprise_tih_message');
        $this->addSql('ALTER TABLE entreprise_tih_message ADD entreprise_id INT NOT NULL, ADD tih_id INT NOT NULL, DROP id_tih, DROP id_entreprise, CHANGE message message LONGTEXT NOT NULL, CHANGE date_candidature date_candidature DATETIME NOT NULL');
        $this->addSql('ALTER TABLE entreprise_tih_message ADD CONSTRAINT FK_7F953E9BA4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES entreprise (id)');
        $this->addSql('ALTER TABLE entreprise_tih_message ADD CONSTRAINT FK_7F953E9BD2C13886 FOREIGN KEY (tih_id) REFERENCES tih (id)');
        $this->addSql('CREATE INDEX IDX_7F953E9BA4AEAFEA ON entreprise_tih_message (entreprise_id)');
        $this->addSql('CREATE INDEX IDX_7F953E9BD2C13886 ON entreprise_tih_message (tih_id)');
        $this->addSql('ALTER TABLE tih DROP FOREIGN KEY fk_tih_user');
        $this->addSql('ALTER TABLE tih ADD date_mise_ajour DATETIME NOT NULL, DROP date_mise_a_jour, CHANGE disponibilite disponibilite LONGTEXT DEFAULT NULL, CHANGE date_creation date_creation DATETIME NOT NULL, CHANGE cv cv LONGTEXT DEFAULT NULL, CHANGE competences competences LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE tih ADD CONSTRAINT FK_5BEF201AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE tih RENAME INDEX user_id TO UNIQ_5BEF201AA76ED395');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE entreprise_tih_message DROP FOREIGN KEY FK_7F953E9BA4AEAFEA');
        $this->addSql('ALTER TABLE entreprise_tih_message DROP FOREIGN KEY FK_7F953E9BD2C13886');
        $this->addSql('DROP INDEX IDX_7F953E9BA4AEAFEA ON entreprise_tih_message');
        $this->addSql('DROP INDEX IDX_7F953E9BD2C13886 ON entreprise_tih_message');
        $this->addSql('ALTER TABLE entreprise_tih_message ADD id_tih INT DEFAULT NULL, ADD id_entreprise INT DEFAULT NULL, DROP entreprise_id, DROP tih_id, CHANGE message message TEXT DEFAULT NULL, CHANGE date_candidature date_candidature DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE entreprise_tih_message ADD CONSTRAINT fk_entreprise_tih_message_entreprise FOREIGN KEY (id_entreprise) REFERENCES entreprise (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE entreprise_tih_message ADD CONSTRAINT fk_entreprise_tih_message_tih FOREIGN KEY (id_tih) REFERENCES tih (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX fk_entreprise_tih_message_tih ON entreprise_tih_message (id_tih)');
        $this->addSql('CREATE INDEX fk_entreprise_tih_message_entreprise ON entreprise_tih_message (id_entreprise)');
        $this->addSql('ALTER TABLE tih DROP FOREIGN KEY FK_5BEF201AA76ED395');
        $this->addSql('ALTER TABLE tih ADD date_mise_a_jour DATETIME DEFAULT NULL, DROP date_mise_ajour, CHANGE disponibilite disponibilite VARCHAR(50) DEFAULT NULL, CHANGE date_creation date_creation DATETIME DEFAULT NULL, CHANGE cv cv TEXT DEFAULT NULL, CHANGE competences competences TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE tih ADD CONSTRAINT fk_tih_user FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tih RENAME INDEX uniq_5bef201aa76ed395 TO user_id');
    }
}
