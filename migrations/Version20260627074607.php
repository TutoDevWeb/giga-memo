<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260627074607 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {

        // --- 1. On crée les colonnes en acceptant le NULL temporairement ---
        $this->addSql('ALTER TABLE categories ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE images ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE rules ADD user_id INT DEFAULT NULL');

        // --- 2. On attribue toutes tes données réelles existantes à ton User ID 1 ---
        $this->addSql('UPDATE categories SET user_id = 1 WHERE user_id IS NULL');
        $this->addSql('UPDATE images SET user_id = 1 WHERE user_id IS NULL');
        $this->addSql('UPDATE rules SET user_id = 1 WHERE user_id IS NULL');

        // --- 3. Maintenant que c'est propre, on remet le NOT NULL ---
        $this->addSql('ALTER TABLE categories CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE images CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE rules CHANGE user_id user_id INT NOT NULL');

        // --- 4. Et enfin, on applique les index et les clés étrangères en toute sécurité ---
        $this->addSql('ALTER TABLE categories ADD CONSTRAINT FK_3AF34668A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_3AF34668A76ED395 ON categories (user_id)');
        $this->addSql('ALTER TABLE images ADD CONSTRAINT FK_E01FBE6AA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_E01FBE6AA76ED395 ON images (user_id)');
        $this->addSql('ALTER TABLE rules ADD CONSTRAINT FK_899A993CA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_899A993CA76ED395 ON rules (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE categories DROP FOREIGN KEY FK_3AF34668A76ED395');
        $this->addSql('DROP INDEX IDX_3AF34668A76ED395 ON categories');
        $this->addSql('ALTER TABLE categories DROP user_id');
        $this->addSql('ALTER TABLE images DROP FOREIGN KEY FK_E01FBE6AA76ED395');
        $this->addSql('DROP INDEX IDX_E01FBE6AA76ED395 ON images');
        $this->addSql('ALTER TABLE images DROP user_id');
        $this->addSql('ALTER TABLE rules DROP FOREIGN KEY FK_899A993CA76ED395');
        $this->addSql('DROP INDEX IDX_899A993CA76ED395 ON rules');
        $this->addSql('ALTER TABLE rules DROP user_id');
    }
}
