<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260201225124 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE file (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, path VARCHAR(1000) NOT NULL, uuid BINARY(16) NOT NULL, size BIGINT NOT NULL, type VARCHAR(255) NOT NULL, owner_id INT NOT NULL, parent_id INT DEFAULT NULL, INDEX IDX_8C9F36107E3C61F9 (owner_id), INDEX IDX_8C9F3610727ACA70 (parent_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('ALTER TABLE file ADD CONSTRAINT FK_8C9F36107E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE file ADD CONSTRAINT FK_8C9F3610727ACA70 FOREIGN KEY (parent_id) REFERENCES folder (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE file DROP FOREIGN KEY FK_8C9F36107E3C61F9');
        $this->addSql('ALTER TABLE file DROP FOREIGN KEY FK_8C9F3610727ACA70');
        $this->addSql('DROP TABLE file');
    }
}
