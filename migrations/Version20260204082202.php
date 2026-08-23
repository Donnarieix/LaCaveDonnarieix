<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260204082202 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE request');
        $this->addSql('ALTER TABLE storage_request ADD requested_unit VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE request (id INT AUTO_INCREMENT NOT NULL, requested_unit VARCHAR(255) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_uca1400_ai_ci`, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_uca1400_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE storage_request DROP requested_unit');
    }
}
