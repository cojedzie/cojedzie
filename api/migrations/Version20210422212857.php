<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210422212857 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('CREATE TABLE federated_connection_entity (id VARCHAR(255) NOT NULL, server_id VARCHAR(255) DEFAULT NULL, url VARCHAR(255) NOT NULL, open_time DATETIME NOT NULL, close_time DATETIME DEFAULT NULL, last_check DATETIME NOT NULL, next_check DATETIME NOT NULL, failures INTEGER NOT NULL, failures_total INTEGER NOT NULL, state VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE federated_server_entity (id VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, maintainer VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('DROP TABLE federated_connection_entity');
        $this->addSql('DROP TABLE federated_server_entity');
    }
}
