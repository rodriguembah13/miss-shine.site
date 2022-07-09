<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220708151937 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE billet ADD candidat_id INT DEFAULT NULL, ADD edition_id INT DEFAULT NULL, ADD firstname VARCHAR(255) NOT NULL, ADD lastname VARCHAR(255) NOT NULL, ADD ville VARCHAR(255) NOT NULL, ADD email VARCHAR(255) NOT NULL, ADD phone VARCHAR(255) NOT NULL, ADD amount DOUBLE PRECISION NOT NULL, ADD created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE billet ADD CONSTRAINT FK_1F034AF68D0EB82 FOREIGN KEY (candidat_id) REFERENCES candidat (id)');
        $this->addSql('ALTER TABLE billet ADD CONSTRAINT FK_1F034AF674281A5E FOREIGN KEY (edition_id) REFERENCES edition (id)');
        $this->addSql('CREATE INDEX IDX_1F034AF68D0EB82 ON billet (candidat_id)');
        $this->addSql('CREATE INDEX IDX_1F034AF674281A5E ON billet (edition_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE billet DROP FOREIGN KEY FK_1F034AF68D0EB82');
        $this->addSql('ALTER TABLE billet DROP FOREIGN KEY FK_1F034AF674281A5E');
        $this->addSql('DROP INDEX IDX_1F034AF68D0EB82 ON billet');
        $this->addSql('DROP INDEX IDX_1F034AF674281A5E ON billet');
        $this->addSql('ALTER TABLE billet DROP candidat_id, DROP edition_id, DROP firstname, DROP lastname, DROP ville, DROP email, DROP phone, DROP amount, DROP created_at');
    }
}
