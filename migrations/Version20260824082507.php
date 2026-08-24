<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260824082507 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE couples 
                                    CHANGE todo_run pending_for_run TINYINT DEFAULT 1 NOT NULL, 
                                    CHANGE todo_review pending_for_review TINYINT DEFAULT 1 NOT NULL,
                                    CHANGE select_review flagged_for_review TINYINT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE couples 
                                    CHANGE pending_for_run todo_run TINYINT DEFAULT 1 NOT NULL, 
                                    CHANGE pending_for_review todo_review TINYINT DEFAULT 1 NOT NULL,
                                    CHANGE flagged_for_review select_review TINYINT DEFAULT 0 NOT NULL');
    }
}
