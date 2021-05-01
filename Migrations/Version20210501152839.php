<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210501152839 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_F6F2DC7AA76ED395');
        $this->addSql('CREATE TEMPORARY TABLE __temp__bug_report AS SELECT id, user_id, title, comment, date FROM bug_report');
        $this->addSql('DROP TABLE bug_report');
        $this->addSql('CREATE TABLE bug_report (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER NOT NULL, title VARCHAR(255) NOT NULL COLLATE BINARY, date DATETIME NOT NULL, comment CLOB NOT NULL, CONSTRAINT FK_F6F2DC7AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO bug_report (id, user_id, title, comment, date) SELECT id, user_id, title, comment, date FROM __temp__bug_report');
        $this->addSql('DROP TABLE __temp__bug_report');
        $this->addSql('CREATE INDEX IDX_F6F2DC7AA76ED395 ON bug_report (user_id)');
        $this->addSql('DROP INDEX IDX_9474526CF675F31B');
        $this->addSql('DROP INDEX IDX_9474526C4B89032C');
        $this->addSql('CREATE TEMPORARY TABLE __temp__comment AS SELECT id, post_id, author_id, content, published_at FROM comment');
        $this->addSql('DROP TABLE comment');
        $this->addSql('CREATE TABLE comment (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, post_id INTEGER NOT NULL, author_id INTEGER NOT NULL, content CLOB NOT NULL COLLATE BINARY, published_at DATETIME NOT NULL, CONSTRAINT FK_9474526C4B89032C FOREIGN KEY (post_id) REFERENCES lease_request (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_9474526CF675F31B FOREIGN KEY (author_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO comment (id, post_id, author_id, content, published_at) SELECT id, post_id, author_id, content, published_at FROM __temp__comment');
        $this->addSql('DROP TABLE __temp__comment');
        $this->addSql('CREATE INDEX IDX_9474526CF675F31B ON comment (author_id)');
        $this->addSql('CREATE INDEX IDX_9474526C4B89032C ON comment (post_id)');
        $this->addSql('DROP INDEX IDX_9BE13C3CF675F31B');
        $this->addSql('CREATE TEMPORARY TABLE __temp__lease_request AS SELECT id, author_id, title, slug, summary, published_at, start_date, end_date, association_type, price, num_attendants, association, contract, contract_signed, read, paid, key_deliver, key_return, deposit_retour, status FROM lease_request');
        $this->addSql('DROP TABLE lease_request');
        $this->addSql('CREATE TABLE lease_request (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, author_id INTEGER DEFAULT NULL, title VARCHAR(255) NOT NULL COLLATE BINARY, slug VARCHAR(255) NOT NULL COLLATE BINARY, summary VARCHAR(255) NOT NULL COLLATE BINARY, published_at DATETIME NOT NULL, start_date DATETIME DEFAULT NULL, end_date DATETIME DEFAULT NULL, association_type VARCHAR(255) NOT NULL COLLATE BINARY, price DOUBLE PRECISION DEFAULT NULL, num_attendants INTEGER NOT NULL, association VARCHAR(255) NOT NULL COLLATE BINARY, contract VARCHAR(255) DEFAULT NULL COLLATE BINARY, contract_signed VARCHAR(255) DEFAULT NULL COLLATE BINARY, read BOOLEAN DEFAULT NULL, paid DOUBLE PRECISION DEFAULT NULL, key_deliver DATETIME DEFAULT NULL, key_return DATETIME DEFAULT NULL, deposit_retour INTEGER NOT NULL, status INTEGER DEFAULT NULL, CONSTRAINT FK_9BE13C3CF675F31B FOREIGN KEY (author_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO lease_request (id, author_id, title, slug, summary, published_at, start_date, end_date, association_type, price, num_attendants, association, contract, contract_signed, read, paid, key_deliver, key_return, deposit_retour, status) SELECT id, author_id, title, slug, summary, published_at, start_date, end_date, association_type, price, num_attendants, association, contract, contract_signed, read, paid, key_deliver, key_return, deposit_retour, status FROM __temp__lease_request');
        $this->addSql('DROP TABLE __temp__lease_request');
        $this->addSql('CREATE INDEX IDX_9BE13C3CF675F31B ON lease_request (author_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_F6F2DC7AA76ED395');
        $this->addSql('CREATE TEMPORARY TABLE __temp__bug_report AS SELECT id, user_id, title, comment, date FROM bug_report');
        $this->addSql('DROP TABLE bug_report');
        $this->addSql('CREATE TABLE bug_report (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER NOT NULL, title VARCHAR(255) NOT NULL, date DATETIME NOT NULL, comment VARCHAR(255) NOT NULL COLLATE BINARY)');
        $this->addSql('INSERT INTO bug_report (id, user_id, title, comment, date) SELECT id, user_id, title, comment, date FROM __temp__bug_report');
        $this->addSql('DROP TABLE __temp__bug_report');
        $this->addSql('CREATE INDEX IDX_F6F2DC7AA76ED395 ON bug_report (user_id)');
        $this->addSql('DROP INDEX IDX_9474526C4B89032C');
        $this->addSql('DROP INDEX IDX_9474526CF675F31B');
        $this->addSql('CREATE TEMPORARY TABLE __temp__comment AS SELECT id, post_id, author_id, content, published_at FROM comment');
        $this->addSql('DROP TABLE comment');
        $this->addSql('CREATE TABLE comment (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, post_id INTEGER NOT NULL, author_id INTEGER NOT NULL, content CLOB NOT NULL, published_at DATETIME NOT NULL)');
        $this->addSql('INSERT INTO comment (id, post_id, author_id, content, published_at) SELECT id, post_id, author_id, content, published_at FROM __temp__comment');
        $this->addSql('DROP TABLE __temp__comment');
        $this->addSql('CREATE INDEX IDX_9474526C4B89032C ON comment (post_id)');
        $this->addSql('CREATE INDEX IDX_9474526CF675F31B ON comment (author_id)');
        $this->addSql('DROP INDEX IDX_9BE13C3CF675F31B');
        $this->addSql('CREATE TEMPORARY TABLE __temp__lease_request AS SELECT id, author_id, title, slug, summary, published_at, start_date, end_date, association_type, price, status, num_attendants, association, contract, contract_signed, read, paid, key_deliver, key_return, deposit_retour FROM lease_request');
        $this->addSql('DROP TABLE lease_request');
        $this->addSql('CREATE TABLE lease_request (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, author_id INTEGER DEFAULT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, summary VARCHAR(255) NOT NULL, published_at DATETIME NOT NULL, start_date DATETIME DEFAULT NULL, end_date DATETIME DEFAULT NULL, association_type VARCHAR(255) NOT NULL, price DOUBLE PRECISION DEFAULT NULL, status INTEGER DEFAULT NULL, num_attendants INTEGER NOT NULL, association VARCHAR(255) NOT NULL, contract VARCHAR(255) DEFAULT NULL, contract_signed VARCHAR(255) DEFAULT NULL, read BOOLEAN DEFAULT NULL, paid DOUBLE PRECISION DEFAULT NULL, key_deliver DATETIME DEFAULT NULL, key_return DATETIME DEFAULT NULL, deposit_retour INTEGER NOT NULL)');
        $this->addSql('INSERT INTO lease_request (id, author_id, title, slug, summary, published_at, start_date, end_date, association_type, price, status, num_attendants, association, contract, contract_signed, read, paid, key_deliver, key_return, deposit_retour) SELECT id, author_id, title, slug, summary, published_at, start_date, end_date, association_type, price, status, num_attendants, association, contract, contract_signed, read, paid, key_deliver, key_return, deposit_retour FROM __temp__lease_request');
        $this->addSql('DROP TABLE __temp__lease_request');
        $this->addSql('CREATE INDEX IDX_9BE13C3CF675F31B ON lease_request (author_id)');
    }
}
