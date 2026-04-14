<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260414110953 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE rules (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, content LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE couples CHANGE todo_run todo_run TINYINT(1) DEFAULT true NOT NULL, CHANGE todo_review todo_review TINYINT(1) DEFAULT true NOT NULL, CHANGE select_review select_review TINYINT(1) DEFAULT false NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE rules');
        $this->addSql('ALTER TABLE couples CHANGE todo_run todo_run TINYINT(1) DEFAULT 1 NOT NULL, CHANGE todo_review todo_review TINYINT(1) DEFAULT 1 NOT NULL, CHANGE select_review select_review TINYINT(1) DEFAULT 0 NOT NULL');
    }
}
