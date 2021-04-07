<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191119140642 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('DROP INDEX IDX_9BE13C3CF675F31B');
        $this->addSql('CREATE TEMPORARY TABLE __temp__lease_request AS SELECT id, author_id, title, slug, summary, published_at, start_date, end_date, association_type, price, status, num_attendants, association, contract, contract_signed, read, paid, key_deliver, key_return, deposit_retour FROM lease_request');
        $this->addSql('DROP TABLE lease_request');
        $this->addSql('CREATE TABLE lease_request (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, author_id INTEGER DEFAULT NULL, title VARCHAR(255) NOT NULL COLLATE BINARY, slug VARCHAR(255) NOT NULL COLLATE BINARY, summary VARCHAR(255) NOT NULL COLLATE BINARY, published_at DATETIME NOT NULL, start_date DATETIME DEFAULT NULL, end_date DATETIME DEFAULT NULL, association_type VARCHAR(255) NOT NULL COLLATE BINARY, price DOUBLE PRECISION DEFAULT NULL, num_attendants INTEGER NOT NULL, association VARCHAR(255) NOT NULL COLLATE BINARY, contract VARCHAR(255) DEFAULT NULL COLLATE BINARY, contract_signed VARCHAR(255) DEFAULT NULL COLLATE BINARY, read BOOLEAN DEFAULT NULL, paid DOUBLE PRECISION DEFAULT NULL, key_deliver DATETIME DEFAULT NULL, key_return DATETIME DEFAULT NULL, deposit_retour INTEGER NOT NULL, status INTEGER DEFAULT NULL)');
        $this->addSql('INSERT INTO lease_request (id, author_id, title, slug, summary, published_at, start_date, end_date, association_type, price, status, num_attendants, association, contract, contract_signed, read, paid, key_deliver, key_return, deposit_retour) SELECT id, author_id, title, slug, summary, published_at, start_date, end_date, association_type, price, status, num_attendants, association, contract, contract_signed, read, paid, key_deliver, key_return, deposit_retour FROM __temp__lease_request');
        $this->addSql('DROP TABLE __temp__lease_request');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('CREATE TEMPORARY TABLE __temp__lease_request AS SELECT id, title, slug, summary, published_at, start_date, end_date, association_type, price, status, num_attendants, association, contract, contract_signed, read, paid, key_deliver, key_return, deposit_retour, author_id FROM lease_request');
        $this->addSql('DROP TABLE lease_request');
        $this->addSql('CREATE TABLE lease_request (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, summary VARCHAR(255) NOT NULL, published_at DATETIME NOT NULL, start_date DATETIME DEFAULT NULL, end_date DATETIME DEFAULT NULL, association_type VARCHAR(255) NOT NULL, price DOUBLE PRECISION DEFAULT NULL, num_attendants INTEGER NOT NULL, association VARCHAR(255) NOT NULL, contract VARCHAR(255) DEFAULT NULL, contract_signed VARCHAR(255) DEFAULT NULL, read BOOLEAN DEFAULT NULL, paid DOUBLE PRECISION DEFAULT NULL, key_deliver DATETIME DEFAULT NULL, key_return DATETIME DEFAULT NULL, deposit_retour INTEGER NOT NULL, author_id INTEGER DEFAULT NULL, status VARCHAR(255) DEFAULT NULL COLLATE BINARY)');
        $this->addSql('INSERT INTO lease_request (id, title, slug, summary, published_at, start_date, end_date, association_type, price, status, num_attendants, association, contract, contract_signed, read, paid, key_deliver, key_return, deposit_retour, author_id) SELECT id, title, slug, summary, published_at, start_date, end_date, association_type, price, status, num_attendants, association, contract, contract_signed, read, paid, key_deliver, key_return, deposit_retour, author_id FROM __temp__lease_request');
        $this->addSql('DROP TABLE __temp__lease_request');
        $this->addSql('CREATE INDEX IDX_9BE13C3CF675F31B ON lease_request (author_id)');
    }
}
