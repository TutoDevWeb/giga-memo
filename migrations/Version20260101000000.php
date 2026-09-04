<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\IrreversibleMigration;

/**
 * Migration de base (genèse) : les tables categories, couples, couples_rules,
 * faqs, images, messenger_messages et rules existaient déjà en base avant la
 * mise en place du système de migrations (elles avaient été créées via
 * `doctrine:schema:create`). Aucune migration ne les créait donc, ce qui
 * empêchait de faire tourner la chaîne de migrations sur une base vierge
 * (CI, nouvel environnement...). Cette migration comble ce trou en recréant
 * ces tables dans leur état d'origine, tel qu'il était juste avant
 * Version20260623123215 (la première migration réellement exécutée) ; les
 * migrations suivantes appliquent ensuite les évolutions normalement.
 *
 * `IF NOT EXISTS` rend cette migration sans effet sur les bases existantes
 * (dev, prod) où ces tables sont déjà présentes.
 */
final class Version20260101000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Tables de base (categories, couples, couples_rules, faqs, images, messenger_messages, rules) préexistantes au système de migrations.';
    }

    public function up(Schema $schema): void
    {
        // Ordre imposé par les clés étrangères : categories et faqs d'abord (faqs -> categories),
        // puis couples (-> faqs), images (-> couples) et rules (-> faqs), et enfin couples_rules
        // (-> couples, rules). messenger_messages n'a pas de dépendance.
        $this->addSql('CREATE TABLE IF NOT EXISTS categories (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE IF NOT EXISTS faqs (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', duration VARCHAR(255) DEFAULT NULL COMMENT \'(DC2Type:dateinterval)\', category_id INT DEFAULT NULL, layer_type VARCHAR(255) DEFAULT \'layer_one\' NOT NULL, INDEX IDX_8934BEE512469DE2 (category_id), PRIMARY KEY (id), CONSTRAINT FK_8934BEE512469DE2 FOREIGN KEY (category_id) REFERENCES categories (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE IF NOT EXISTS couples (id INT AUTO_INCREMENT NOT NULL, num INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', question LONGTEXT DEFAULT NULL, reponse LONGTEXT DEFAULT NULL, todo_run TINYINT(1) NOT NULL DEFAULT 1, todo_review TINYINT(1) NOT NULL DEFAULT 1, select_review TINYINT(1) NOT NULL DEFAULT 0, faq_id INT NOT NULL, regle LONGTEXT DEFAULT NULL, INDEX IDX_14D6768A81BEC8C2 (faq_id), PRIMARY KEY (id), CONSTRAINT FK_14D6768A81BEC8C2 FOREIGN KEY (faq_id) REFERENCES faqs (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE IF NOT EXISTS images (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, couple_id INT NOT NULL, INDEX IDX_E01FBE6AF66468CA (couple_id), PRIMARY KEY (id), CONSTRAINT FK_E01FBE6AF66468CA FOREIGN KEY (couple_id) REFERENCES couples (id) ON DELETE CASCADE) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE IF NOT EXISTS rules (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) DEFAULT NULL, content LONGTEXT DEFAULT NULL, faq_id INT NOT NULL, INDEX IDX_899A993C81BEC8C2 (faq_id), PRIMARY KEY (id), CONSTRAINT FK_899A993C81BEC8C2 FOREIGN KEY (faq_id) REFERENCES faqs (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE IF NOT EXISTS couples_rules (couples_id INT NOT NULL, rules_id INT NOT NULL, INDEX IDX_D20BBA989C36F2E3 (couples_id), INDEX IDX_D20BBA98FB699244 (rules_id), PRIMARY KEY (couples_id, rules_id), CONSTRAINT FK_D20BBA989C36F2E3 FOREIGN KEY (couples_id) REFERENCES couples (id) ON DELETE CASCADE, CONSTRAINT FK_D20BBA98FB699244 FOREIGN KEY (rules_id) REFERENCES rules (id) ON DELETE CASCADE) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE IF NOT EXISTS messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E016BA31DB (delivered_at), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E0FB7336F0 (queue_name), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        throw new IrreversibleMigration('Cette migration reconstitue un état préexistant au système de migrations ; elle ne doit pas être annulée (risque de perte de données sur les bases existantes).');
    }
}
