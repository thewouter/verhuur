<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191210204218 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('CREATE TABLE front_message (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, start_date DATETIME NOT NULL, end_date DATETIME NOT NULL, title VARCHAR(255) NOT NULL, body VARCHAR(1023) NOT NULL)');
        $this->addSql('DROP INDEX UNIQ_8FB094A1F85E0677');
        $this->addSql('CREATE TEMPORARY TABLE __temp__user AS SELECT id, full_name, username, email, password, roles, address, phone, password_reset, confirmed FROM user');
        $this->addSql('DROP TABLE user');
        $this->addSql('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, full_name VARCHAR(255) NOT NULL COLLATE BINARY, username VARCHAR(255) NOT NULL COLLATE BINARY, email VARCHAR(255) NOT NULL COLLATE BINARY, password VARCHAR(255) NOT NULL COLLATE BINARY, roles CLOB NOT NULL COLLATE BINARY --(DC2Type:json)
        , address CLOB NOT NULL COLLATE BINARY, phone CLOB NOT NULL COLLATE BINARY, password_reset CLOB DEFAULT NULL COLLATE BINARY, confirmed INTEGER NOT NULL)');
        $this->addSql('INSERT INTO user (id, full_name, username, email, password, roles, address, phone, password_reset, confirmed) SELECT id, full_name, username, email, password, roles, address, phone, password_reset, confirmed FROM __temp__user');
        $this->addSql('DROP TABLE __temp__user');
        $this->addSql('DROP INDEX IDX_53AD8F83F675F31B');
        $this->addSql('DROP INDEX IDX_53AD8F834B89032C');
        $this->addSql('CREATE TEMPORARY TABLE __temp__comment AS SELECT id, post_id, author_id, content, published_at FROM comment');
        $this->addSql('DROP TABLE comment');
        $this->addSql('CREATE TABLE comment (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, content CLOB NOT NULL COLLATE BINARY, published_at DATETIME NOT NULL, post_id INTEGER NOT NULL, author_id INTEGER NOT NULL)');
        $this->addSql('INSERT INTO comment (id, post_id, author_id, content, published_at) SELECT id, post_id, author_id, content, published_at FROM __temp__comment');
        $this->addSql('DROP TABLE __temp__comment');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('DROP TABLE front_message');
        $this->addSql('CREATE TEMPORARY TABLE __temp__comment AS SELECT id, content, published_at, post_id, author_id FROM comment');
        $this->addSql('DROP TABLE comment');
        $this->addSql('CREATE TABLE comment (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, content CLOB NOT NULL, published_at DATETIME NOT NULL, post_id INTEGER DEFAULT NULL, author_id INTEGER DEFAULT NULL)');
        $this->addSql('INSERT INTO comment (id, content, published_at, post_id, author_id) SELECT id, content, published_at, post_id, author_id FROM __temp__comment');
        $this->addSql('DROP TABLE __temp__comment');
        $this->addSql('CREATE INDEX IDX_53AD8F83F675F31B ON comment (author_id)');
        $this->addSql('CREATE INDEX IDX_53AD8F834B89032C ON comment (post_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__user AS SELECT id, full_name, username, email, password, roles, address, phone, password_reset, confirmed FROM user');
        $this->addSql('DROP TABLE user');
        $this->addSql('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, full_name VARCHAR(255) NOT NULL, username VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, roles CLOB NOT NULL --(DC2Type:json)
        , address CLOB NOT NULL, phone CLOB NOT NULL, password_reset CLOB DEFAULT NULL, confirmed INTEGER NOT NULL)');
        $this->addSql('INSERT INTO user (id, full_name, username, email, password, roles, address, phone, password_reset, confirmed) SELECT id, full_name, username, email, password, roles, address, phone, password_reset, confirmed FROM __temp__user');
        $this->addSql('DROP TABLE __temp__user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8FB094A1F85E0677 ON user (username)');
    }
}
