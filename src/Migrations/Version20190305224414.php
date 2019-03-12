<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190305224414 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('DROP INDEX IDX_9BE13C3CF675F31B');
        $this->addSql('CREATE TEMPORARY TABLE __temp__lease_request AS SELECT id, title, slug, summary, published_at, start_date, end_date, association_type, price FROM lease_request');
        $this->addSql('DROP TABLE lease_request');
        $this->addSql('CREATE TABLE lease_request (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(255) NOT NULL COLLATE BINARY, slug VARCHAR(255) NOT NULL COLLATE BINARY, summary VARCHAR(255) NOT NULL COLLATE BINARY, published_at DATETIME NOT NULL, start_date DATETIME DEFAULT NULL, end_date DATETIME DEFAULT NULL, association_type VARCHAR(255) NOT NULL COLLATE BINARY, price DOUBLE PRECISION DEFAULT NULL)');
        $this->addSql('INSERT INTO lease_request (id, title, slug, summary, published_at, start_date, end_date, association_type, price) SELECT id, title, slug, summary, published_at, start_date, end_date, association_type, price FROM __temp__lease_request');
        $this->addSql('DROP TABLE __temp__lease_request');
        $this->addSql('DROP INDEX IDX_5ACE3AF08D93D649');
        $this->addSql('DROP INDEX IDX_5ACE3AF0BAD26311');
        $this->addSql('CREATE TEMPORARY TABLE __temp__post_tag AS SELECT user, tag_id FROM post_tag');
        $this->addSql('DROP TABLE post_tag');
        $this->addSql('CREATE TABLE post_tag (user INTEGER NOT NULL, tag_id INTEGER NOT NULL, PRIMARY KEY(user, tag_id), CONSTRAINT FK_5ACE3AF08D93D649 FOREIGN KEY (user) REFERENCES lease_request (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_5ACE3AF0BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO post_tag (user, tag_id) SELECT user, tag_id FROM __temp__post_tag');
        $this->addSql('DROP TABLE __temp__post_tag');
        $this->addSql('CREATE INDEX IDX_5ACE3AF08D93D649 ON post_tag (user)');
        $this->addSql('CREATE INDEX IDX_5ACE3AF0BAD26311 ON post_tag (tag_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('CREATE TEMPORARY TABLE __temp__lease_request AS SELECT id, title, slug, summary, published_at, start_date, end_date, association_type, price FROM lease_request');
        $this->addSql('DROP TABLE lease_request');
        $this->addSql('CREATE TABLE lease_request (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, summary VARCHAR(255) NOT NULL, published_at DATETIME NOT NULL, start_date DATETIME DEFAULT NULL, end_date DATETIME DEFAULT NULL, association_type VARCHAR(255) NOT NULL, price DOUBLE PRECISION DEFAULT NULL, author_id INTEGER DEFAULT NULL)');
        $this->addSql('INSERT INTO lease_request (id, title, slug, summary, published_at, start_date, end_date, association_type, price) SELECT id, title, slug, summary, published_at, start_date, end_date, association_type, price FROM __temp__lease_request');
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
