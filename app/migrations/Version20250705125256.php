<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250705125256 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE competence (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tih_competence (tih_id INT NOT NULL, competence_id INT NOT NULL, INDEX IDX_1958A632D2C13886 (tih_id), INDEX IDX_1958A63215761DAB (competence_id), PRIMARY KEY(tih_id, competence_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tih_competence ADD CONSTRAINT FK_1958A632D2C13886 FOREIGN KEY (tih_id) REFERENCES tih (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tih_competence ADD CONSTRAINT FK_1958A63215761DAB FOREIGN KEY (competence_id) REFERENCES competence (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tih DROP competences');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tih_competence DROP FOREIGN KEY FK_1958A632D2C13886');
        $this->addSql('ALTER TABLE tih_competence DROP FOREIGN KEY FK_1958A63215761DAB');
        $this->addSql('DROP TABLE competence');
        $this->addSql('DROP TABLE tih_competence');
        $this->addSql('ALTER TABLE tih ADD competences LONGTEXT DEFAULT NULL');
    }
}
