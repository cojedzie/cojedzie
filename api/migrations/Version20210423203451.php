<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210423203451 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Create entities related to federated servers.';
    }

    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('CREATE TABLE federated_connection (id BLOB NOT NULL --(DC2Type:uuid)
        , server_id BLOB DEFAULT NULL --(DC2Type:uuid)
        , url VARCHAR(255) NOT NULL, open_time DATETIME NOT NULL, close_time DATETIME DEFAULT NULL, last_check DATETIME NOT NULL, next_check DATETIME NOT NULL, failures INTEGER NOT NULL, failures_total INTEGER NOT NULL, state VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_D3AF7DF01844E6B7 ON federated_connection (server_id)');
        $this->addSql('CREATE TABLE federated_server (id BLOB NOT NULL --(DC2Type:uuid)
        , email VARCHAR(255) NOT NULL, maintainer VARCHAR(255) DEFAULT NULL, allowed_url VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('DROP TABLE federated_connection');
        $this->addSql('DROP TABLE federated_server');
    }
}
