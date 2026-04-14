<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260228063913 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE couples CHANGE todo_run todo_run TINYINT(1) DEFAULT true NOT NULL, CHANGE todo_review todo_review TINYINT(1) DEFAULT true NOT NULL, CHANGE select_review select_review TINYINT(1) DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE faqs ADD duration VARCHAR(255) DEFAULT NULL COMMENT \'(DC2Type:dateinterval)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE couples CHANGE todo_run todo_run TINYINT(1) DEFAULT 1 NOT NULL, CHANGE todo_review todo_review TINYINT(1) DEFAULT 1 NOT NULL, CHANGE select_review select_review TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE faqs DROP duration');
    }
}
