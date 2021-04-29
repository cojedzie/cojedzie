<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210429144033 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('DROP INDEX IDX_D3AF7DF01844E6B7');
        $this->addSql('CREATE TEMPORARY TABLE __temp__federated_connection AS SELECT id, server_id, url, opened_at, closed_at, last_check, next_check, failures, failures_total, state FROM federated_connection');
        $this->addSql('DROP TABLE federated_connection');
        $this->addSql('CREATE TABLE federated_connection (id BLOB NOT NULL --(DC2Type:uuid)
        , server_id BLOB DEFAULT NULL --(DC2Type:uuid)
        , url VARCHAR(255) NOT NULL COLLATE BINARY, opened_at DATETIME NOT NULL, closed_at DATETIME DEFAULT NULL, last_check DATETIME DEFAULT NULL, next_check DATETIME NOT NULL, failures INTEGER NOT NULL, failures_total INTEGER NOT NULL, state VARCHAR(255) NOT NULL COLLATE BINARY, last_status CLOB default NULL, PRIMARY KEY(id), CONSTRAINT FK_D3AF7DF01844E6B7 FOREIGN KEY (server_id) REFERENCES federated_server (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO federated_connection (id, server_id, url, opened_at, closed_at, last_check, next_check, failures, failures_total, state, last_status) SELECT id, server_id, url, opened_at, closed_at, last_check, next_check, failures, failures_total, state FROM __temp__federated_connection');
        $this->addSql('DROP TABLE __temp__federated_connection');
        $this->addSql('CREATE INDEX IDX_D3AF7DF01844E6B7 ON federated_connection (server_id)');
        $this->addSql('ALTER TABLE federated_server ADD COLUMN secret VARCHAR(255) NOT NULL default \'\'');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('DROP INDEX IDX_D3AF7DF01844E6B7');
        $this->addSql('CREATE TEMPORARY TABLE __temp__federated_connection AS SELECT id, server_id, url, opened_at, closed_at, last_check, next_check, failures, failures_total, state FROM federated_connection');
        $this->addSql('DROP TABLE federated_connection');
        $this->addSql('CREATE TABLE federated_connection (id BLOB NOT NULL, url VARCHAR(255) NOT NULL, opened_at DATETIME NOT NULL, closed_at DATETIME DEFAULT NULL, last_check DATETIME DEFAULT NULL, next_check DATETIME NOT NULL, failures INTEGER NOT NULL, failures_total INTEGER NOT NULL, state VARCHAR(255) NOT NULL, server_id BLOB DEFAULT NULL --,,,,(DC2Type:uuid)
        , PRIMARY KEY(id))');
        $this->addSql('INSERT INTO federated_connection (id, server_id, url, opened_at, closed_at, last_check, next_check, failures, failures_total, state) SELECT id, server_id, url, opened_at, closed_at, last_check, next_check, failures, failures_total, state FROM __temp__federated_connection');
        $this->addSql('DROP TABLE __temp__federated_connection');
        $this->addSql('CREATE INDEX IDX_D3AF7DF01844E6B7 ON federated_connection (server_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__federated_server AS SELECT id, email, maintainer, allowed_url FROM federated_server');
        $this->addSql('DROP TABLE federated_server');
        $this->addSql('CREATE TABLE federated_server (id BLOB NOT NULL --(DC2Type:uuid)
        , email VARCHAR(255) NOT NULL, maintainer VARCHAR(255) DEFAULT NULL, allowed_url VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO federated_server (id, email, maintainer, allowed_url) SELECT id, email, maintainer, allowed_url FROM __temp__federated_server');
        $this->addSql('DROP TABLE __temp__federated_server');
    }
}
