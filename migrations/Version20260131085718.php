<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260131085718 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__form_field_reference AS SELECT id, author_id FROM form_field_reference');
        $this->addSql('DROP TABLE form_field_reference');
        $this->addSql('CREATE TABLE form_field_reference (id VARCHAR(255) NOT NULL, author_id INTEGER NOT NULL, PRIMARY KEY (id), CONSTRAINT FK_FBC69240F675F31B FOREIGN KEY (author_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO form_field_reference (id, author_id) SELECT id, author_id FROM __temp__form_field_reference');
        $this->addSql('DROP TABLE __temp__form_field_reference');
        $this->addSql('CREATE INDEX IDX_FBC69240F675F31B ON form_field_reference (author_id)');
        $this->addSql('ALTER TABLE subscriber ADD COLUMN country VARCHAR(2) DEFAULT NULL');
        $this->addSql('ALTER TABLE subscriber ADD COLUMN timezone VARCHAR(50) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__form_field_reference AS SELECT id, author_id FROM form_field_reference');
        $this->addSql('DROP TABLE form_field_reference');
        $this->addSql('CREATE TABLE form_field_reference (id VARCHAR(255) NOT NULL, author_id INTEGER NOT NULL, CONSTRAINT FK_FBC69240F675F31B FOREIGN KEY (author_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO form_field_reference (id, author_id) SELECT id, author_id FROM __temp__form_field_reference');
        $this->addSql('DROP TABLE __temp__form_field_reference');
        $this->addSql('CREATE INDEX IDX_FBC69240F675F31B ON form_field_reference (author_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__subscriber AS SELECT id, email, name, subscribed_at, is_confirmed, confirmed_at, source, unsubscribed_at, locale, notes, ip_address FROM subscriber');
        $this->addSql('DROP TABLE subscriber');
        $this->addSql('CREATE TABLE subscriber (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, name VARCHAR(100) DEFAULT NULL, subscribed_at DATETIME NOT NULL, is_confirmed BOOLEAN NOT NULL, confirmed_at DATETIME DEFAULT NULL, source VARCHAR(20) NOT NULL, unsubscribed_at DATETIME DEFAULT NULL, locale VARCHAR(5) NOT NULL, notes CLOB DEFAULT NULL, ip_address VARCHAR(45) DEFAULT NULL)');
        $this->addSql('INSERT INTO subscriber (id, email, name, subscribed_at, is_confirmed, confirmed_at, source, unsubscribed_at, locale, notes, ip_address) SELECT id, email, name, subscribed_at, is_confirmed, confirmed_at, source, unsubscribed_at, locale, notes, ip_address FROM __temp__subscriber');
        $this->addSql('DROP TABLE __temp__subscriber');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AD005B69E7927C74 ON subscriber (email)');
    }
}
