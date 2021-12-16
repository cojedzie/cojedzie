<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211216200929 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Initial Schema';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE track (id VARCHAR(255) NOT NULL, line_id VARCHAR(255) DEFAULT NULL, final_id INT DEFAULT NULL, provider_id VARCHAR(255) DEFAULT NULL, variant VARCHAR(16) DEFAULT NULL, description VARCHAR(256) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_d6e3f8a613d41b2d ON track (final_id)');
        $this->addSql('CREATE INDEX idx_d6e3f8a6a53a8aa ON track (provider_id)');
        $this->addSql('CREATE INDEX idx_d6e3f8a64d7b7542 ON track (line_id)');

        $this->addSql('CREATE TABLE track_stop (id SERIAL NOT NULL, stop_id VARCHAR(255) DEFAULT NULL, track_id VARCHAR(255) DEFAULT NULL, sequence INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_24003eb35ed23c43 ON track_stop (track_id)');
        $this->addSql('CREATE INDEX idx_24003eb33902063d ON track_stop (stop_id)');
        $this->addSql('CREATE UNIQUE INDEX stop_in_track_idx ON track_stop (stop_id, track_id, sequence)');

        $this->addSql('CREATE TABLE operator (id VARCHAR(255) NOT NULL, provider_id VARCHAR(255) DEFAULT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) DEFAULT NULL, url VARCHAR(255) DEFAULT NULL, phone VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_d7a6a781a53a8aa ON operator (provider_id)');

        $this->addSql('CREATE TABLE stop (id VARCHAR(255) NOT NULL, provider_id VARCHAR(255) DEFAULT NULL, name VARCHAR(255) NOT NULL, group_name VARCHAR(255) DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, variant VARCHAR(255) DEFAULT NULL, latitude DOUBLE PRECISION DEFAULT NULL, longitude DOUBLE PRECISION DEFAULT NULL, on_demand BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX group_idx ON stop (group_name)');
        $this->addSql('CREATE INDEX idx_b95616b6a53a8aa ON stop (provider_id)');

        $this->addSql('CREATE TABLE trip_stop (id SERIAL NOT NULL, stop_id VARCHAR(255) DEFAULT NULL, trip_id VARCHAR(255) DEFAULT NULL, sequence INT NOT NULL, arrival TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, departure TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_926e85dda5bc2e0e ON trip_stop (trip_id)');
        $this->addSql('CREATE INDEX idx_926e85dd3902063d ON trip_stop (stop_id)');

        $this->addSql('CREATE TABLE trip (id VARCHAR(255) NOT NULL, operator_id VARCHAR(255) DEFAULT NULL, track_id VARCHAR(255) DEFAULT NULL, provider_id VARCHAR(255) DEFAULT NULL, variant VARCHAR(255) DEFAULT NULL, note VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_7656f53b5ed23c43 ON trip (track_id)');
        $this->addSql('CREATE INDEX idx_7656f53b584598a3 ON trip (operator_id)');
        $this->addSql('CREATE INDEX idx_7656f53ba53a8aa ON trip (provider_id)');

        $this->addSql('CREATE TABLE line (id VARCHAR(255) NOT NULL, operator_id VARCHAR(255) DEFAULT NULL, provider_id VARCHAR(255) DEFAULT NULL, symbol VARCHAR(16) NOT NULL, type VARCHAR(20) NOT NULL, fast BOOLEAN NOT NULL, night BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_d114b4f6a53a8aa ON line (provider_id)');
        $this->addSql('CREATE INDEX idx_d114b4f6584598a3 ON line (operator_id)');

        $this->addSql('CREATE TABLE provider (id VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, class VARCHAR(255) NOT NULL, update_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');

        $this->addSql('CREATE TABLE messenger_messages (id BIGSERIAL NOT NULL, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_75ea56e0e3bd61ce ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX idx_75ea56e0fb7336f0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX idx_75ea56e016ba31db ON messenger_messages (delivered_at)');

        $this->addSql('CREATE TABLE federated_server (id UUID NOT NULL, email VARCHAR(255) NOT NULL, maintainer VARCHAR(255) DEFAULT NULL, allowed_url VARCHAR(255) NOT NULL, secret VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN federated_server.id IS \'(DC2Type:uuid)\'');

        $this->addSql('CREATE TABLE federated_connection (id UUID NOT NULL, server_id UUID DEFAULT NULL, url VARCHAR(255) NOT NULL, opened_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, closed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, last_check TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, next_check TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, failures INT NOT NULL, failures_total INT NOT NULL, state VARCHAR(255) NOT NULL, last_status TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_d3af7df01844e6b7 ON federated_connection (server_id)');
        $this->addSql('COMMENT ON COLUMN federated_connection.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN federated_connection.server_id IS \'(DC2Type:uuid)\'');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE track');
        $this->addSql('DROP TABLE track_stop');
        $this->addSql('DROP TABLE operator');
        $this->addSql('DROP TABLE stop');
        $this->addSql('DROP TABLE trip_stop');
        $this->addSql('DROP TABLE trip');
        $this->addSql('DROP TABLE line');
        $this->addSql('DROP TABLE provider');
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('DROP TABLE federated_server');
        $this->addSql('DROP TABLE federated_connection');
    }
}
