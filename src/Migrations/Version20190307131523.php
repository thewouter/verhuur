<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190307131523 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('DROP INDEX IDX_53AD8F83F675F31B');
        $this->addSql('DROP INDEX IDX_53AD8F834B89032C');
        $this->addSql('CREATE TEMPORARY TABLE __temp__comment AS SELECT id, post_id, author_id, content, published_at FROM comment');
        $this->addSql('DROP TABLE comment');
        $this->addSql('CREATE TABLE comment (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, post_id INTEGER DEFAULT NULL, author_id INTEGER DEFAULT NULL, content CLOB NOT NULL COLLATE BINARY, published_at DATETIME NOT NULL, CONSTRAINT FK_9474526CF675F31B FOREIGN KEY (author_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_9474526C4B89032C FOREIGN KEY (post_id) REFERENCES lease_request (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO comment (id, post_id, author_id, content, published_at) SELECT id, post_id, author_id, content, published_at FROM __temp__comment');
        $this->addSql('DROP TABLE __temp__comment');
        $this->addSql('CREATE INDEX IDX_53AD8F83F675F31B ON comment (author_id)');
        $this->addSql('CREATE INDEX IDX_53AD8F834B89032C ON comment (post_id)');
        $this->addSql('DROP INDEX IDX_9BE13C3CF675F31B');
        $this->addSql('CREATE TEMPORARY TABLE __temp__lease_request AS SELECT id, author_id, title, slug, summary, published_at, start_date, end_date, association_type, price, status, num_attendants, association, contract FROM lease_request');
        $this->addSql('DROP TABLE lease_request');
        $this->addSql('CREATE TABLE lease_request (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, author_id INTEGER DEFAULT NULL, title VARCHAR(255) NOT NULL COLLATE BINARY, slug VARCHAR(255) NOT NULL COLLATE BINARY, summary VARCHAR(255) NOT NULL COLLATE BINARY, published_at DATETIME NOT NULL, start_date DATETIME DEFAULT NULL, end_date DATETIME DEFAULT NULL, association_type VARCHAR(255) NOT NULL COLLATE BINARY, price DOUBLE PRECISION DEFAULT NULL, status VARCHAR(255) DEFAULT NULL COLLATE BINARY, num_attendants INTEGER NOT NULL, association VARCHAR(255) NOT NULL COLLATE BINARY, contract VARCHAR(255) DEFAULT NULL COLLATE BINARY, contract_signed VARCHAR(255) DEFAULT NULL, CONSTRAINT FK_9BE13C3CF675F31B FOREIGN KEY (author_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO lease_request (id, author_id, title, slug, summary, published_at, start_date, end_date, association_type, price, status, num_attendants, association, contract) SELECT id, author_id, title, slug, summary, published_at, start_date, end_date, association_type, price, status, num_attendants, association, contract FROM __temp__lease_request');
        $this->addSql('DROP TABLE __temp__lease_request');
        $this->addSql('CREATE INDEX IDX_9BE13C3CF675F31B ON lease_request (author_id)');
        $this->addSql('DROP INDEX IDX_5ACE3AF0BAD26311');
        $this->addSql('DROP INDEX IDX_5ACE3AF08D93D649');
        $this->addSql('CREATE TEMPORARY TABLE __temp__post_tag AS SELECT user, tag_id FROM post_tag');
        $this->addSql('DROP TABLE post_tag');
        $this->addSql('CREATE TABLE post_tag (user INTEGER NOT NULL, tag_id INTEGER NOT NULL, PRIMARY KEY(user, tag_id), CONSTRAINT FK_5ACE3AF08D93D649 FOREIGN KEY (user) REFERENCES lease_request (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_5ACE3AF0BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO post_tag (user, tag_id) SELECT user, tag_id FROM __temp__post_tag');
        $this->addSql('DROP TABLE __temp__post_tag');
        $this->addSql('CREATE INDEX IDX_5ACE3AF0BAD26311 ON post_tag (tag_id)');
        $this->addSql('CREATE INDEX IDX_5ACE3AF08D93D649 ON post_tag (user)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('DROP INDEX IDX_53AD8F83F675F31B');
        $this->addSql('DROP INDEX IDX_53AD8F834B89032C');
        $this->addSql('CREATE TEMPORARY TABLE __temp__comment AS SELECT id, author_id, post_id, content, published_at FROM comment');
        $this->addSql('DROP TABLE comment');
        $this->addSql('CREATE TABLE comment (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, author_id INTEGER DEFAULT NULL, post_id INTEGER DEFAULT NULL, content CLOB NOT NULL, published_at DATETIME NOT NULL)');
        $this->addSql('INSERT INTO comment (id, author_id, post_id, content, published_at) SELECT id, author_id, post_id, content, published_at FROM __temp__comment');
        $this->addSql('DROP TABLE __temp__comment');
        $this->addSql('CREATE INDEX IDX_53AD8F83F675F31B ON comment (author_id)');
        $this->addSql('CREATE INDEX IDX_53AD8F834B89032C ON comment (post_id)');
        $this->addSql('DROP INDEX IDX_9BE13C3CF675F31B');
        $this->addSql('CREATE TEMPORARY TABLE __temp__lease_request AS SELECT id, author_id, title, slug, summary, published_at, start_date, end_date, association_type, price, status, num_attendants, association, contract FROM lease_request');
        $this->addSql('DROP TABLE lease_request');
        $this->addSql('CREATE TABLE lease_request (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, author_id INTEGER DEFAULT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, summary VARCHAR(255) NOT NULL, published_at DATETIME NOT NULL, start_date DATETIME DEFAULT NULL, end_date DATETIME DEFAULT NULL, association_type VARCHAR(255) NOT NULL, price DOUBLE PRECISION DEFAULT NULL, status VARCHAR(255) DEFAULT NULL, num_attendants INTEGER NOT NULL, association VARCHAR(255) NOT NULL, contract VARCHAR(255) DEFAULT NULL)');
        $this->addSql('INSERT INTO lease_request (id, author_id, title, slug, summary, published_at, start_date, end_date, association_type, price, status, num_attendants, association, contract) SELECT id, author_id, title, slug, summary, published_at, start_date, end_date, association_type, price, status, num_attendants, association, contract FROM __temp__lease_request');
        $this->addSql('DROP TABLE __temp__lease_request');
        $this->addSql('CREATE INDEX IDX_9BE13C3CF675F31B ON lease_request (author_id)');
        $this->addSql('DROP INDEX IDX_5ACE3AF08D93D649');
        $this->addSql('DROP INDEX IDX_5ACE3AF0BAD26311');
        $this->addSql('CREATE TEMPORARY TABLE __temp__post_tag AS SELECT user, tag_id FROM post_tag');
        $this->addSql('DROP TABLE post_tag');
        $this->addSql('CREATE TABLE post_tag (user INTEGER NOT NULL, tag_id INTEGER NOT NULL, PRIMARY KEY(user, tag_id))');
        $this->addSql('INSERT INTO post_tag (user, tag_id) SELECT user, tag_id FROM __temp__post_tag');
        $this->addSql('DROP TABLE __temp__post_tag');
        $this->addSql('CREATE INDEX IDX_5ACE3AF08D93D649 ON post_tag (user)');
        $this->addSql('CREATE INDEX IDX_5ACE3AF0BAD26311 ON post_tag (tag_id)');
    }
}
