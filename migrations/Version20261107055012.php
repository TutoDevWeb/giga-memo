<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261107055012 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE categories (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, user_id INT NOT NULL, INDEX IDX_3AF34668A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE couples (id INT AUTO_INCREMENT NOT NULL, num INT NOT NULL, created_at DATETIME NOT NULL, question LONGTEXT DEFAULT NULL, reponse LONGTEXT DEFAULT NULL, pending_for_run TINYINT DEFAULT 1 NOT NULL, pending_for_review TINYINT DEFAULT 1 NOT NULL, flagged_for_review TINYINT DEFAULT 0 NOT NULL, faq_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_14D6768A81BEC8C2 (faq_id), INDEX IDX_14D6768AA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE couples_rules (couples_id INT NOT NULL, rules_id INT NOT NULL, INDEX IDX_D20BBA989C36F2E3 (couples_id), INDEX IDX_D20BBA98FB699244 (rules_id), PRIMARY KEY (couples_id, rules_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE faqs (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, created_at DATETIME NOT NULL, duration VARCHAR(255) DEFAULT NULL, category_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_8934BEE512469DE2 (category_id), INDEX IDX_8934BEE5A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE images (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, couple_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_E01FBE6AF66468CA (couple_id), INDEX IDX_E01FBE6AA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE planning (id INT AUTO_INCREMENT NOT NULL, time_frame VARCHAR(255) NOT NULL, frequency VARCHAR(255) NOT NULL, date_start DATETIME DEFAULT NULL, status VARCHAR(255) NOT NULL, faq_id INT NOT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_D499BFF681BEC8C2 (faq_id), INDEX IDX_D499BFF6A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE reset_password_request (id INT AUTO_INCREMENT NOT NULL, selector VARCHAR(20) NOT NULL, hashed_token VARCHAR(100) NOT NULL, requested_at DATETIME NOT NULL, expires_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_7CE748AA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE rules (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) DEFAULT NULL, content LONGTEXT DEFAULT NULL, faq_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_899A993C81BEC8C2 (faq_id), INDEX IDX_899A993CA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, is_verified TINYINT NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE categories ADD CONSTRAINT FK_3AF34668A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE couples ADD CONSTRAINT FK_14D6768A81BEC8C2 FOREIGN KEY (faq_id) REFERENCES faqs (id)');
        $this->addSql('ALTER TABLE couples ADD CONSTRAINT FK_14D6768AA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE couples_rules ADD CONSTRAINT FK_D20BBA989C36F2E3 FOREIGN KEY (couples_id) REFERENCES couples (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE couples_rules ADD CONSTRAINT FK_D20BBA98FB699244 FOREIGN KEY (rules_id) REFERENCES rules (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE faqs ADD CONSTRAINT FK_8934BEE512469DE2 FOREIGN KEY (category_id) REFERENCES categories (id)');
        $this->addSql('ALTER TABLE faqs ADD CONSTRAINT FK_8934BEE5A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE images ADD CONSTRAINT FK_E01FBE6AF66468CA FOREIGN KEY (couple_id) REFERENCES couples (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE images ADD CONSTRAINT FK_E01FBE6AA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE planning ADD CONSTRAINT FK_D499BFF681BEC8C2 FOREIGN KEY (faq_id) REFERENCES faqs (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE planning ADD CONSTRAINT FK_D499BFF6A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE rules ADD CONSTRAINT FK_899A993C81BEC8C2 FOREIGN KEY (faq_id) REFERENCES faqs (id)');
        $this->addSql('ALTER TABLE rules ADD CONSTRAINT FK_899A993CA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE categories DROP FOREIGN KEY FK_3AF34668A76ED395');
        $this->addSql('ALTER TABLE couples DROP FOREIGN KEY FK_14D6768A81BEC8C2');
        $this->addSql('ALTER TABLE couples DROP FOREIGN KEY FK_14D6768AA76ED395');
        $this->addSql('ALTER TABLE couples_rules DROP FOREIGN KEY FK_D20BBA989C36F2E3');
        $this->addSql('ALTER TABLE couples_rules DROP FOREIGN KEY FK_D20BBA98FB699244');
        $this->addSql('ALTER TABLE faqs DROP FOREIGN KEY FK_8934BEE512469DE2');
        $this->addSql('ALTER TABLE faqs DROP FOREIGN KEY FK_8934BEE5A76ED395');
        $this->addSql('ALTER TABLE images DROP FOREIGN KEY FK_E01FBE6AF66468CA');
        $this->addSql('ALTER TABLE images DROP FOREIGN KEY FK_E01FBE6AA76ED395');
        $this->addSql('ALTER TABLE planning DROP FOREIGN KEY FK_D499BFF681BEC8C2');
        $this->addSql('ALTER TABLE planning DROP FOREIGN KEY FK_D499BFF6A76ED395');
        $this->addSql('ALTER TABLE reset_password_request DROP FOREIGN KEY FK_7CE748AA76ED395');
        $this->addSql('ALTER TABLE rules DROP FOREIGN KEY FK_899A993C81BEC8C2');
        $this->addSql('ALTER TABLE rules DROP FOREIGN KEY FK_899A993CA76ED395');
        $this->addSql('DROP TABLE categories');
        $this->addSql('DROP TABLE couples');
        $this->addSql('DROP TABLE couples_rules');
        $this->addSql('DROP TABLE faqs');
        $this->addSql('DROP TABLE images');
        $this->addSql('DROP TABLE planning');
        $this->addSql('DROP TABLE reset_password_request');
        $this->addSql('DROP TABLE rules');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
