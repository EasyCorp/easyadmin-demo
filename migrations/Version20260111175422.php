<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260111175422 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial database creation';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE category (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, description CLOB DEFAULT NULL, color VARCHAR(7) NOT NULL, icon VARCHAR(50) DEFAULT NULL, position INTEGER NOT NULL, created_at DATETIME NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_64C19C1989D9B62 ON category (slug)');
        $this->addSql('CREATE TABLE comment (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, content CLOB NOT NULL, published_at DATETIME NOT NULL, status VARCHAR(20) NOT NULL, author_id INTEGER NOT NULL, post_id INTEGER NOT NULL, CONSTRAINT FK_9474526CF675F31B FOREIGN KEY (author_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_9474526C4B89032C FOREIGN KEY (post_id) REFERENCES post (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_9474526CF675F31B ON comment (author_id)');
        $this->addSql('CREATE INDEX IDX_9474526C4B89032C ON comment (post_id)');
        $this->addSql('CREATE TABLE form_field_reference (id VARCHAR(255) NOT NULL, author_id INTEGER NOT NULL, CONSTRAINT FK_FBC69240F675F31B FOREIGN KEY (author_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_FBC69240F675F31B ON form_field_reference (author_id)');
        $this->addSql('CREATE TABLE post (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, summary VARCHAR(255) NOT NULL, content CLOB NOT NULL, published_at DATETIME DEFAULT NULL, status VARCHAR(20) NOT NULL, featured_image VARCHAR(255) DEFAULT NULL, scheduled_at DATETIME DEFAULT NULL, view_count INTEGER NOT NULL, is_featured BOOLEAN NOT NULL, series_position INTEGER DEFAULT NULL, author_id INTEGER NOT NULL, category_id INTEGER DEFAULT NULL, series_id INTEGER DEFAULT NULL, CONSTRAINT FK_5A8A6C8DF675F31B FOREIGN KEY (author_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_5A8A6C8D12469DE2 FOREIGN KEY (category_id) REFERENCES category (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_5A8A6C8D5278319C FOREIGN KEY (series_id) REFERENCES series (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_5A8A6C8DF675F31B ON post (author_id)');
        $this->addSql('CREATE INDEX IDX_5A8A6C8D12469DE2 ON post (category_id)');
        $this->addSql('CREATE INDEX IDX_5A8A6C8D5278319C ON post (series_id)');
        $this->addSql('CREATE TABLE post_tag (post_id INTEGER NOT NULL, tag_id INTEGER NOT NULL, PRIMARY KEY (post_id, tag_id), CONSTRAINT FK_5ACE3AF04B89032C FOREIGN KEY (post_id) REFERENCES post (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_5ACE3AF0BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_5ACE3AF04B89032C ON post_tag (post_id)');
        $this->addSql('CREATE INDEX IDX_5ACE3AF0BAD26311 ON post_tag (tag_id)');
        $this->addSql('CREATE TABLE series (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, cover_image VARCHAR(255) DEFAULT NULL, is_complete BOOLEAN NOT NULL, created_at DATETIME NOT NULL, author_id INTEGER NOT NULL, CONSTRAINT FK_3A10012DF675F31B FOREIGN KEY (author_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3A10012D989D9B62 ON series (slug)');
        $this->addSql('CREATE INDEX IDX_3A10012DF675F31B ON series (author_id)');
        $this->addSql('CREATE TABLE subscriber (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, name VARCHAR(100) DEFAULT NULL, subscribed_at DATETIME NOT NULL, is_confirmed BOOLEAN NOT NULL, confirmed_at DATETIME DEFAULT NULL, source VARCHAR(20) NOT NULL, unsubscribed_at DATETIME DEFAULT NULL, locale VARCHAR(5) NOT NULL, notes CLOB DEFAULT NULL, ip_address VARCHAR(45) DEFAULT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AD005B69E7927C74 ON subscriber (email)');
        $this->addSql('CREATE TABLE tag (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL)');
        $this->addSql('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, full_name VARCHAR(255) NOT NULL, username VARCHAR(50) NOT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, roles CLOB NOT NULL, bio CLOB DEFAULT NULL, website VARCHAR(255) DEFAULT NULL, twitter_handle VARCHAR(50) DEFAULT NULL, is_verified BOOLEAN NOT NULL, last_login_at DATETIME DEFAULT NULL)');
        $this->addSql('CREATE TABLE messenger_messages (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, body CLOB NOT NULL, headers CLOB NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL)');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages (queue_name, available_at, delivered_at, id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE comment');
        $this->addSql('DROP TABLE form_field_reference');
        $this->addSql('DROP TABLE post');
        $this->addSql('DROP TABLE post_tag');
        $this->addSql('DROP TABLE series');
        $this->addSql('DROP TABLE subscriber');
        $this->addSql('DROP TABLE tag');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
