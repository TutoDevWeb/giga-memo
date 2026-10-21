<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261021085221 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE planning DROP FOREIGN KEY `FK_D499BFF681BEC8C2`');
        $this->addSql('ALTER TABLE planning CHANGE faq_id faq_id INT NOT NULL');
        $this->addSql('ALTER TABLE planning ADD CONSTRAINT FK_D499BFF681BEC8C2 FOREIGN KEY (faq_id) REFERENCES faqs (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE planning DROP FOREIGN KEY FK_D499BFF681BEC8C2');
        $this->addSql('ALTER TABLE planning CHANGE faq_id faq_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE planning ADD CONSTRAINT `FK_D499BFF681BEC8C2` FOREIGN KEY (faq_id) REFERENCES faqs (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
