<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260623152713 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    // public function up(Schema $schema): void
    // {
    //     // this up() migration is auto-generated, please modify it to your needs
    //     $this->addSql('ALTER TABLE couples ADD user_id INT NOT NULL');
    //     $this->addSql('ALTER TABLE couples ADD CONSTRAINT FK_14D6768AA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
    //     $this->addSql('CREATE INDEX IDX_14D6768AA76ED395 ON couples (user_id)');
    //     $this->addSql('ALTER TABLE faqs ADD user_id INT NOT NULL');
    //     $this->addSql('ALTER TABLE faqs ADD CONSTRAINT FK_8934BEE5A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
    //     $this->addSql('CREATE INDEX IDX_8934BEE5A76ED395 ON faqs (user_id)');
    // }


    public function up(Schema $schema): void
    {
        // --- 1. On crée les colonnes en acceptant le NULL temporairement ---
        $this->addSql('ALTER TABLE couples ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE faqs ADD user_id INT DEFAULT NULL');

        // --- 2. On attribue toutes tes données réelles existantes à ton User ID 1 ---
        $this->addSql('UPDATE couples SET user_id = 1 WHERE user_id IS NULL');
        $this->addSql('UPDATE faqs SET user_id = 1 WHERE user_id IS NULL');

        // --- 3. Maintenant que c'est propre, on remet le NOT NULL ---
        $this->addSql('ALTER TABLE couples CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE faqs CHANGE user_id user_id INT NOT NULL');

        // --- 4. Et enfin, on applique les index et les clés étrangères en toute sécurité ---
        $this->addSql('ALTER TABLE couples ADD CONSTRAINT FK_14D6768AA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_14D6768AA76ED395 ON couples (user_id)');

        $this->addSql('ALTER TABLE faqs ADD CONSTRAINT FK_8934BEE5A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_8934BEE5A76ED395 ON faqs (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE couples DROP FOREIGN KEY FK_14D6768AA76ED395');
        $this->addSql('DROP INDEX IDX_14D6768AA76ED395 ON couples');
        $this->addSql('ALTER TABLE couples DROP user_id');
        $this->addSql('ALTER TABLE faqs DROP FOREIGN KEY FK_8934BEE5A76ED395');
        $this->addSql('DROP INDEX IDX_8934BEE5A76ED395 ON faqs');
        $this->addSql('ALTER TABLE faqs DROP user_id');
    }
}
