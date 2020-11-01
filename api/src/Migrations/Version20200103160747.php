<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200103160747 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('DROP INDEX IDX_D6E3F8A6A53A8AA');
        $this->addSql('DROP INDEX IDX_D6E3F8A64D7B7542');
        $this->addSql('CREATE TEMPORARY TABLE __temp__track AS SELECT id, line_id, provider_id, variant, description FROM track');
        $this->addSql('DROP TABLE track');
        $this->addSql('CREATE TABLE track (id VARCHAR(255) NOT NULL COLLATE BINARY, line_id VARCHAR(255) DEFAULT NULL COLLATE BINARY, provider_id VARCHAR(255) DEFAULT NULL COLLATE BINARY, variant VARCHAR(16) DEFAULT NULL COLLATE BINARY, description VARCHAR(256) DEFAULT NULL COLLATE BINARY, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO track (id, line_id, provider_id, variant, description) SELECT id, line_id, provider_id, variant, description FROM __temp__track');
        $this->addSql('DROP TABLE __temp__track');
        $this->addSql('DROP INDEX IDX_D7A6A781A53A8AA');
        $this->addSql('CREATE TEMPORARY TABLE __temp__operator AS SELECT id, provider_id, name, email, url, phone FROM operator');
        $this->addSql('DROP TABLE operator');
        $this->addSql('CREATE TABLE operator (id VARCHAR(255) NOT NULL COLLATE BINARY, provider_id VARCHAR(255) DEFAULT NULL COLLATE BINARY, name VARCHAR(255) NOT NULL COLLATE BINARY, email VARCHAR(255) DEFAULT NULL COLLATE BINARY, url VARCHAR(255) DEFAULT NULL COLLATE BINARY, phone VARCHAR(255) DEFAULT NULL COLLATE BINARY, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO operator (id, provider_id, name, email, url, phone) SELECT id, provider_id, name, email, url, phone FROM __temp__operator');
        $this->addSql('DROP TABLE __temp__operator');
        $this->addSql('DROP INDEX IDX_B95616B6A53A8AA');
        $this->addSql('CREATE TEMPORARY TABLE __temp__stop AS SELECT id, provider_id, name, description, variant, latitude, longitude, on_demand FROM stop');
        $this->addSql('DROP TABLE stop');
        $this->addSql('CREATE TABLE stop (id VARCHAR(255) NOT NULL COLLATE BINARY, provider_id VARCHAR(255) DEFAULT NULL COLLATE BINARY, name VARCHAR(255) NOT NULL COLLATE BINARY, description VARCHAR(255) DEFAULT NULL COLLATE BINARY, variant VARCHAR(255) DEFAULT NULL COLLATE BINARY, latitude DOUBLE PRECISION DEFAULT NULL, longitude DOUBLE PRECISION DEFAULT NULL, on_demand BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO stop (id, provider_id, name, description, variant, latitude, longitude, on_demand) SELECT id, provider_id, name, description, variant, latitude, longitude, on_demand FROM __temp__stop');
        $this->addSql('DROP TABLE __temp__stop');
        $this->addSql('DROP INDEX IDX_D114B4F6A53A8AA');
        $this->addSql('DROP INDEX IDX_D114B4F6584598A3');
        $this->addSql('CREATE TEMPORARY TABLE __temp__line AS SELECT id, operator_id, provider_id, symbol, type, fast, night FROM line');
        $this->addSql('DROP TABLE line');
        $this->addSql('CREATE TABLE line (id VARCHAR(255) NOT NULL COLLATE BINARY, operator_id VARCHAR(255) DEFAULT NULL COLLATE BINARY, provider_id VARCHAR(255) DEFAULT NULL COLLATE BINARY, symbol VARCHAR(16) NOT NULL COLLATE BINARY, type VARCHAR(20) NOT NULL COLLATE BINARY, fast BOOLEAN NOT NULL, night BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO line (id, operator_id, provider_id, symbol, type, fast, night) SELECT id, operator_id, provider_id, symbol, type, fast, night FROM __temp__line');
        $this->addSql('DROP TABLE __temp__line');
        $this->addSql('CREATE TEMPORARY TABLE __temp__provider AS SELECT id, name, class, update_date FROM provider');
        $this->addSql('DROP TABLE provider');
        $this->addSql('CREATE TABLE provider (id VARCHAR(255) NOT NULL COLLATE BINARY, name VARCHAR(255) NOT NULL COLLATE BINARY, class VARCHAR(255) NOT NULL COLLATE BINARY, update_date DATETIME NOT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO provider (id, name, class, update_date) SELECT id, name, class, update_date FROM __temp__provider');
        $this->addSql('DROP TABLE __temp__provider');
        $this->addSql('DROP INDEX IDX_24003EB35ED23C43');
        $this->addSql('DROP INDEX IDX_24003EB33902063D');
        $this->addSql('CREATE TEMPORARY TABLE __temp__track_stop AS SELECT stop_id, track_id, sequence FROM track_stop');
        $this->addSql('DROP TABLE track_stop');
        $this->addSql('CREATE TABLE track_stop (stop_id VARCHAR(255) NOT NULL COLLATE BINARY, track_id VARCHAR(255) NOT NULL COLLATE BINARY, sequence INTEGER NOT NULL, PRIMARY KEY(stop_id, track_id, sequence))');
        $this->addSql('INSERT INTO track_stop (stop_id, track_id, sequence) SELECT stop_id, track_id, sequence FROM __temp__track_stop');
        $this->addSql('DROP TABLE __temp__track_stop');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('CREATE TEMPORARY TABLE __temp__line AS SELECT id, symbol, type, fast, night, operator_id, provider_id FROM line');
        $this->addSql('DROP TABLE line');
        $this->addSql('CREATE TABLE line (id VARCHAR(255) NOT NULL, symbol VARCHAR(16) NOT NULL, type VARCHAR(20) NOT NULL, fast BOOLEAN NOT NULL, night BOOLEAN NOT NULL, operator_id VARCHAR(255) DEFAULT NULL, provider_id VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO line (id, symbol, type, fast, night, operator_id, provider_id) SELECT id, symbol, type, fast, night, operator_id, provider_id FROM __temp__line');
        $this->addSql('DROP TABLE __temp__line');
        $this->addSql('CREATE INDEX IDX_D114B4F6A53A8AA ON line (provider_id)');
        $this->addSql('CREATE INDEX IDX_D114B4F6584598A3 ON line (operator_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__operator AS SELECT id, name, email, url, phone, provider_id FROM operator');
        $this->addSql('DROP TABLE operator');
        $this->addSql('CREATE TABLE operator (id VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) DEFAULT NULL, url VARCHAR(255) DEFAULT NULL, phone VARCHAR(255) DEFAULT NULL, provider_id VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO operator (id, name, email, url, phone, provider_id) SELECT id, name, email, url, phone, provider_id FROM __temp__operator');
        $this->addSql('DROP TABLE __temp__operator');
        $this->addSql('CREATE INDEX IDX_D7A6A781A53A8AA ON operator (provider_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__provider AS SELECT id, name, class, update_date FROM provider');
        $this->addSql('DROP TABLE provider');
        $this->addSql('CREATE TABLE provider (id VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, class VARCHAR(255) NOT NULL, update_date DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO provider (id, name, class, update_date) SELECT id, name, class, update_date FROM __temp__provider');
        $this->addSql('DROP TABLE __temp__provider');
        $this->addSql('CREATE TEMPORARY TABLE __temp__stop AS SELECT id, name, description, variant, latitude, longitude, on_demand, provider_id FROM stop');
        $this->addSql('DROP TABLE stop');
        $this->addSql('CREATE TABLE stop (id VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, variant VARCHAR(255) DEFAULT NULL, latitude DOUBLE PRECISION DEFAULT NULL, longitude DOUBLE PRECISION DEFAULT NULL, on_demand BOOLEAN NOT NULL, provider_id VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO stop (id, name, description, variant, latitude, longitude, on_demand, provider_id) SELECT id, name, description, variant, latitude, longitude, on_demand, provider_id FROM __temp__stop');
        $this->addSql('DROP TABLE __temp__stop');
        $this->addSql('CREATE INDEX IDX_B95616B6A53A8AA ON stop (provider_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__track AS SELECT id, variant, description, line_id, provider_id FROM track');
        $this->addSql('DROP TABLE track');
        $this->addSql('CREATE TABLE track (id VARCHAR(255) NOT NULL, variant VARCHAR(16) DEFAULT NULL, description VARCHAR(256) DEFAULT NULL, line_id VARCHAR(255) DEFAULT NULL, provider_id VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO track (id, variant, description, line_id, provider_id) SELECT id, variant, description, line_id, provider_id FROM __temp__track');
        $this->addSql('DROP TABLE __temp__track');
        $this->addSql('CREATE INDEX IDX_D6E3F8A6A53A8AA ON track (provider_id)');
        $this->addSql('CREATE INDEX IDX_D6E3F8A64D7B7542 ON track (line_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__track_stop AS SELECT sequence, stop_id, track_id FROM track_stop');
        $this->addSql('DROP TABLE track_stop');
        $this->addSql('CREATE TABLE track_stop (stop_id VARCHAR(255) NOT NULL, track_id VARCHAR(255) NOT NULL, sequence INTEGER NOT NULL, PRIMARY KEY(stop_id, track_id))');
        $this->addSql('INSERT INTO track_stop (sequence, stop_id, track_id) SELECT sequence, stop_id, track_id FROM __temp__track_stop');
        $this->addSql('DROP TABLE __temp__track_stop');
        $this->addSql('CREATE INDEX IDX_24003EB35ED23C43 ON track_stop (track_id)');
        $this->addSql('CREATE INDEX IDX_24003EB33902063D ON track_stop (stop_id)');
    }
}
