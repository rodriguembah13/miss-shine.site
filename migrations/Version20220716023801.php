<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220716023801 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE model_sms (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(255) NOT NULL, message VARCHAR(255) DEFAULT NULL, element1 VARCHAR(255) DEFAULT NULL, element2 VARCHAR(255) DEFAULT NULL, element3 VARCHAR(255) DEFAULT NULL, element4 VARCHAR(255) DEFAULT NULL, element5 VARCHAR(255) DEFAULT NULL, element6 VARCHAR(255) DEFAULT NULL, element7 VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sms (id INT AUTO_INCREMENT NOT NULL, model_sms_id INT DEFAULT NULL, recepteur VARCHAR(255) DEFAULT NULL, telephone VARCHAR(255) DEFAULT NULL, created_at DATETIME DEFAULT NULL, status VARCHAR(255) DEFAULT NULL, message LONGTEXT DEFAULT NULL, INDEX IDX_B0A93A779EBB5AFF (model_sms_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE sms ADD CONSTRAINT FK_B0A93A779EBB5AFF FOREIGN KEY (model_sms_id) REFERENCES model_sms (id)');
        $this->addSql('ALTER TABLE candidat ADD phone VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sms DROP FOREIGN KEY FK_B0A93A779EBB5AFF');
        $this->addSql('DROP TABLE model_sms');
        $this->addSql('DROP TABLE sms');
        $this->addSql('ALTER TABLE candidat DROP phone');
    }
}
