<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260414112826 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE couples_rules (couples_id INT NOT NULL, rules_id INT NOT NULL, INDEX IDX_D20BBA989C36F2E3 (couples_id), INDEX IDX_D20BBA98FB699244 (rules_id), PRIMARY KEY(couples_id, rules_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE couples_rules ADD CONSTRAINT FK_D20BBA989C36F2E3 FOREIGN KEY (couples_id) REFERENCES couples (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE couples_rules ADD CONSTRAINT FK_D20BBA98FB699244 FOREIGN KEY (rules_id) REFERENCES rules (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE couples CHANGE todo_run todo_run TINYINT(1) DEFAULT true NOT NULL, CHANGE todo_review todo_review TINYINT(1) DEFAULT true NOT NULL, CHANGE select_review select_review TINYINT(1) DEFAULT false NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE couples_rules DROP FOREIGN KEY FK_D20BBA989C36F2E3');
        $this->addSql('ALTER TABLE couples_rules DROP FOREIGN KEY FK_D20BBA98FB699244');
        $this->addSql('DROP TABLE couples_rules');
        $this->addSql('ALTER TABLE couples CHANGE todo_run todo_run TINYINT(1) DEFAULT 1 NOT NULL, CHANGE todo_review todo_review TINYINT(1) DEFAULT 1 NOT NULL, CHANGE select_review select_review TINYINT(1) DEFAULT 0 NOT NULL');
    }
}
