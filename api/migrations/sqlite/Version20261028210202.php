<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261028210202 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Bring the schema in line with the mapping on DBAL 4: drop DBAL 3 type comments and collations';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TEMPORARY TABLE __temp__federated_connection AS SELECT id, server_id, url, opened_at, closed_at, last_check, next_check, failures, failures_total, state, last_status FROM federated_connection');
        $this->addSql('DROP TABLE federated_connection');
        $this->addSql('CREATE TABLE federated_connection (id BLOB NOT NULL, server_id BLOB DEFAULT NULL, url VARCHAR(255) NOT NULL, opened_at DATETIME NOT NULL, closed_at DATETIME DEFAULT NULL, last_check DATETIME DEFAULT NULL, next_check DATETIME NOT NULL, failures INTEGER NOT NULL, failures_total INTEGER NOT NULL, state VARCHAR(255) NOT NULL, last_status CLOB DEFAULT NULL, PRIMARY KEY (id), CONSTRAINT FK_D3AF7DF01844E6B7 FOREIGN KEY (server_id) REFERENCES federated_server (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO federated_connection (id, server_id, url, opened_at, closed_at, last_check, next_check, failures, failures_total, state, last_status) SELECT id, server_id, url, opened_at, closed_at, last_check, next_check, failures, failures_total, state, last_status FROM __temp__federated_connection');
        $this->addSql('DROP TABLE __temp__federated_connection');
        $this->addSql('CREATE INDEX IDX_D3AF7DF01844E6B7 ON federated_connection (server_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__federated_server AS SELECT id, email, maintainer, allowed_url, secret FROM federated_server');
        $this->addSql('DROP TABLE federated_server');
        $this->addSql('CREATE TABLE federated_server (id BLOB NOT NULL, email VARCHAR(255) NOT NULL, maintainer VARCHAR(255) DEFAULT NULL, allowed_url VARCHAR(255) NOT NULL, secret VARCHAR(255) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('INSERT INTO federated_server (id, email, maintainer, allowed_url, secret) SELECT id, email, maintainer, allowed_url, secret FROM __temp__federated_server');
        $this->addSql('DROP TABLE __temp__federated_server');
        $this->addSql('CREATE TEMPORARY TABLE __temp__import AS SELECT id, started_at, finished_at FROM import');
        $this->addSql('DROP TABLE import');
        $this->addSql('CREATE TABLE import (id BLOB NOT NULL, started_at DATETIME NOT NULL, finished_at DATETIME DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('INSERT INTO import (id, started_at, finished_at) SELECT id, started_at, finished_at FROM __temp__import');
        $this->addSql('DROP TABLE __temp__import');
        $this->addSql('CREATE TEMPORARY TABLE __temp__line AS SELECT id, operator_id, provider_id, import_id, symbol, type, fast, night FROM line');
        $this->addSql('DROP TABLE line');
        $this->addSql('CREATE TABLE line (id VARCHAR(255) NOT NULL, operator_id VARCHAR(255) DEFAULT NULL, provider_id VARCHAR(255) DEFAULT NULL, import_id BLOB DEFAULT NULL, symbol VARCHAR(16) NOT NULL, type VARCHAR(20) NOT NULL, fast BOOLEAN NOT NULL, night BOOLEAN NOT NULL, PRIMARY KEY (id), CONSTRAINT FK_D114B4F6584598A3 FOREIGN KEY (operator_id) REFERENCES operator (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_D114B4F6A53A8AA FOREIGN KEY (provider_id) REFERENCES provider (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_D114B4F6B6A263D9 FOREIGN KEY (import_id) REFERENCES import (id) ON UPDATE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO line (id, operator_id, provider_id, import_id, symbol, type, fast, night) SELECT id, operator_id, provider_id, import_id, symbol, type, fast, night FROM __temp__line');
        $this->addSql('DROP TABLE __temp__line');
        $this->addSql('CREATE INDEX IDX_D114B4F6B6A263D9 ON line (import_id)');
        $this->addSql('CREATE INDEX IDX_D114B4F6A53A8AA ON line (provider_id)');
        $this->addSql('CREATE INDEX IDX_D114B4F6584598A3 ON line (operator_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__operator AS SELECT id, provider_id, import_id, name, email, url, phone FROM operator');
        $this->addSql('DROP TABLE operator');
        $this->addSql('CREATE TABLE operator (id VARCHAR(255) NOT NULL, provider_id VARCHAR(255) DEFAULT NULL, import_id BLOB DEFAULT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) DEFAULT NULL, url VARCHAR(255) DEFAULT NULL, phone VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id), CONSTRAINT FK_D7A6A781A53A8AA FOREIGN KEY (provider_id) REFERENCES provider (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_D7A6A781B6A263D9 FOREIGN KEY (import_id) REFERENCES import (id) ON UPDATE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO operator (id, provider_id, import_id, name, email, url, phone) SELECT id, provider_id, import_id, name, email, url, phone FROM __temp__operator');
        $this->addSql('DROP TABLE __temp__operator');
        $this->addSql('CREATE INDEX IDX_D7A6A781B6A263D9 ON operator (import_id)');
        $this->addSql('CREATE INDEX IDX_D7A6A781A53A8AA ON operator (provider_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__stop AS SELECT id, provider_id, import_id, name, description, variant, latitude, longitude, on_demand, group_name FROM stop');
        $this->addSql('DROP TABLE stop');
        $this->addSql('CREATE TABLE stop (id VARCHAR(255) NOT NULL, provider_id VARCHAR(255) DEFAULT NULL, import_id BLOB DEFAULT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, variant VARCHAR(255) DEFAULT NULL, latitude DOUBLE PRECISION DEFAULT NULL, longitude DOUBLE PRECISION DEFAULT NULL, on_demand BOOLEAN NOT NULL, group_name VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id), CONSTRAINT FK_B95616B6A53A8AA FOREIGN KEY (provider_id) REFERENCES provider (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_B95616B6B6A263D9 FOREIGN KEY (import_id) REFERENCES import (id) ON UPDATE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO stop (id, provider_id, import_id, name, description, variant, latitude, longitude, on_demand, group_name) SELECT id, provider_id, import_id, name, description, variant, latitude, longitude, on_demand, group_name FROM __temp__stop');
        $this->addSql('DROP TABLE __temp__stop');
        $this->addSql('CREATE INDEX IDX_B95616B6B6A263D9 ON stop (import_id)');
        $this->addSql('CREATE INDEX IDX_B95616B6A53A8AA ON stop (provider_id)');
        $this->addSql('CREATE INDEX group_idx ON stop (group_name)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__track AS SELECT id, line_id, provider_id, final_id, import_id, variant, description FROM track');
        $this->addSql('DROP TABLE track');
        $this->addSql('CREATE TABLE track (id VARCHAR(255) NOT NULL, line_id VARCHAR(255) DEFAULT NULL, provider_id VARCHAR(255) DEFAULT NULL, final_id INTEGER DEFAULT NULL, import_id BLOB DEFAULT NULL, variant VARCHAR(16) DEFAULT NULL, description VARCHAR(256) DEFAULT NULL, PRIMARY KEY (id), CONSTRAINT FK_D6E3F8A64D7B7542 FOREIGN KEY (line_id) REFERENCES line (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_D6E3F8A613D41B2D FOREIGN KEY (final_id) REFERENCES track_stop (id) ON UPDATE NO ACTION ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_D6E3F8A6A53A8AA FOREIGN KEY (provider_id) REFERENCES provider (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_D6E3F8A6B6A263D9 FOREIGN KEY (import_id) REFERENCES import (id) ON UPDATE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO track (id, line_id, provider_id, final_id, import_id, variant, description) SELECT id, line_id, provider_id, final_id, import_id, variant, description FROM __temp__track');
        $this->addSql('DROP TABLE __temp__track');
        $this->addSql('CREATE INDEX IDX_D6E3F8A6B6A263D9 ON track (import_id)');
        $this->addSql('CREATE INDEX IDX_D6E3F8A6A53A8AA ON track (provider_id)');
        $this->addSql('CREATE INDEX IDX_D6E3F8A64D7B7542 ON track (line_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D6E3F8A613D41B2D ON track (final_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__track_stop AS SELECT id, stop_id, track_id, import_id, sequence FROM track_stop');
        $this->addSql('DROP TABLE track_stop');
        $this->addSql('CREATE TABLE track_stop (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, stop_id VARCHAR(255) DEFAULT NULL, track_id VARCHAR(255) DEFAULT NULL, import_id BLOB DEFAULT NULL, sequence INTEGER NOT NULL, CONSTRAINT FK_24003EB33902063D FOREIGN KEY (stop_id) REFERENCES stop (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_24003EB35ED23C43 FOREIGN KEY (track_id) REFERENCES track (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_24003EB3B6A263D9 FOREIGN KEY (import_id) REFERENCES import (id) ON UPDATE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO track_stop (id, stop_id, track_id, import_id, sequence) SELECT id, stop_id, track_id, import_id, sequence FROM __temp__track_stop');
        $this->addSql('DROP TABLE __temp__track_stop');
        $this->addSql('CREATE INDEX IDX_24003EB3B6A263D9 ON track_stop (import_id)');
        $this->addSql('CREATE INDEX IDX_24003EB35ED23C43 ON track_stop (track_id)');
        $this->addSql('CREATE INDEX IDX_24003EB33902063D ON track_stop (stop_id)');
        $this->addSql('CREATE UNIQUE INDEX stop_in_track_idx ON track_stop (stop_id, track_id, sequence)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__trip AS SELECT id, operator_id, track_id, provider_id, import_id, variant, note FROM trip');
        $this->addSql('DROP TABLE trip');
        $this->addSql('CREATE TABLE trip (id VARCHAR(255) NOT NULL, operator_id VARCHAR(255) DEFAULT NULL, track_id VARCHAR(255) DEFAULT NULL, provider_id VARCHAR(255) DEFAULT NULL, import_id BLOB DEFAULT NULL, variant VARCHAR(255) DEFAULT NULL, note VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id), CONSTRAINT FK_7656F53B584598A3 FOREIGN KEY (operator_id) REFERENCES operator (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_7656F53B5ED23C43 FOREIGN KEY (track_id) REFERENCES track (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_7656F53BA53A8AA FOREIGN KEY (provider_id) REFERENCES provider (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_7656F53BB6A263D9 FOREIGN KEY (import_id) REFERENCES import (id) ON UPDATE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO trip (id, operator_id, track_id, provider_id, import_id, variant, note) SELECT id, operator_id, track_id, provider_id, import_id, variant, note FROM __temp__trip');
        $this->addSql('DROP TABLE __temp__trip');
        $this->addSql('CREATE INDEX IDX_7656F53BB6A263D9 ON trip (import_id)');
        $this->addSql('CREATE INDEX IDX_7656F53BA53A8AA ON trip (provider_id)');
        $this->addSql('CREATE INDEX IDX_7656F53B5ED23C43 ON trip (track_id)');
        $this->addSql('CREATE INDEX IDX_7656F53B584598A3 ON trip (operator_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__trip_stop AS SELECT id, stop_id, trip_id, import_id, sequence, arrival, departure FROM trip_stop');
        $this->addSql('DROP TABLE trip_stop');
        $this->addSql('CREATE TABLE trip_stop (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, stop_id VARCHAR(255) DEFAULT NULL, trip_id VARCHAR(255) DEFAULT NULL, import_id BLOB DEFAULT NULL, sequence INTEGER NOT NULL, arrival DATETIME NOT NULL, departure DATETIME NOT NULL, CONSTRAINT FK_926E85DD3902063D FOREIGN KEY (stop_id) REFERENCES stop (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_926E85DDA5BC2E0E FOREIGN KEY (trip_id) REFERENCES trip (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_926E85DDB6A263D9 FOREIGN KEY (import_id) REFERENCES import (id) ON UPDATE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO trip_stop (id, stop_id, trip_id, import_id, sequence, arrival, departure) SELECT id, stop_id, trip_id, import_id, sequence, arrival, departure FROM __temp__trip_stop');
        $this->addSql('DROP TABLE __temp__trip_stop');
        $this->addSql('CREATE INDEX IDX_926E85DDB6A263D9 ON trip_stop (import_id)');
        $this->addSql('CREATE INDEX IDX_926E85DDA5BC2E0E ON trip_stop (trip_id)');
        $this->addSql('CREATE INDEX IDX_926E85DD3902063D ON trip_stop (stop_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TEMPORARY TABLE __temp__federated_connection AS SELECT id, url, opened_at, closed_at, last_check, next_check, failures, failures_total, state, last_status, server_id FROM federated_connection');
        $this->addSql('DROP TABLE federated_connection');
        $this->addSql('CREATE TABLE federated_connection (id BLOB NOT NULL --(DC2Type:uuid)
        , url VARCHAR(255) NOT NULL, opened_at DATETIME NOT NULL, closed_at DATETIME DEFAULT NULL, last_check DATETIME DEFAULT NULL, next_check DATETIME NOT NULL, failures INTEGER NOT NULL, failures_total INTEGER NOT NULL, state VARCHAR(255) NOT NULL, last_status CLOB DEFAULT NULL, server_id BLOB DEFAULT NULL --(DC2Type:uuid)
        , PRIMARY KEY (id), CONSTRAINT FK_D3AF7DF01844E6B7 FOREIGN KEY (server_id) REFERENCES federated_server (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO federated_connection (id, url, opened_at, closed_at, last_check, next_check, failures, failures_total, state, last_status, server_id) SELECT id, url, opened_at, closed_at, last_check, next_check, failures, failures_total, state, last_status, server_id FROM __temp__federated_connection');
        $this->addSql('DROP TABLE __temp__federated_connection');
        $this->addSql('CREATE INDEX IDX_D3AF7DF01844E6B7 ON federated_connection (server_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__federated_server AS SELECT id, email, maintainer, allowed_url, secret FROM federated_server');
        $this->addSql('DROP TABLE federated_server');
        $this->addSql('CREATE TABLE federated_server (id BLOB NOT NULL --(DC2Type:uuid)
        , email VARCHAR(255) NOT NULL, maintainer VARCHAR(255) DEFAULT NULL, allowed_url VARCHAR(255) NOT NULL, secret VARCHAR(255) DEFAULT \'\' NOT NULL, PRIMARY KEY (id))');
        $this->addSql('INSERT INTO federated_server (id, email, maintainer, allowed_url, secret) SELECT id, email, maintainer, allowed_url, secret FROM __temp__federated_server');
        $this->addSql('DROP TABLE __temp__federated_server');
        $this->addSql('CREATE TEMPORARY TABLE __temp__import AS SELECT id, started_at, finished_at FROM import');
        $this->addSql('DROP TABLE import');
        $this->addSql('CREATE TABLE import (id BLOB NOT NULL --(DC2Type:uuid)
        , started_at DATETIME NOT NULL, finished_at DATETIME DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('INSERT INTO import (id, started_at, finished_at) SELECT id, started_at, finished_at FROM __temp__import');
        $this->addSql('DROP TABLE __temp__import');
        $this->addSql('CREATE TEMPORARY TABLE __temp__line AS SELECT symbol, type, fast, night, id, operator_id, provider_id, import_id FROM line');
        $this->addSql('DROP TABLE line');
        $this->addSql('CREATE TABLE line (symbol VARCHAR(16) NOT NULL, type VARCHAR(20) NOT NULL, fast BOOLEAN NOT NULL, night BOOLEAN NOT NULL, id VARCHAR(255) NOT NULL, operator_id VARCHAR(255) DEFAULT NULL, provider_id VARCHAR(255) DEFAULT NULL, import_id BLOB DEFAULT NULL --(DC2Type:uuid)
        , PRIMARY KEY (id), CONSTRAINT FK_D114B4F6584598A3 FOREIGN KEY (operator_id) REFERENCES operator (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_D114B4F6A53A8AA FOREIGN KEY (provider_id) REFERENCES provider (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_D114B4F6B6A263D9 FOREIGN KEY (import_id) REFERENCES import (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO line (symbol, type, fast, night, id, operator_id, provider_id, import_id) SELECT symbol, type, fast, night, id, operator_id, provider_id, import_id FROM __temp__line');
        $this->addSql('DROP TABLE __temp__line');
        $this->addSql('CREATE INDEX IDX_D114B4F6584598A3 ON line (operator_id)');
        $this->addSql('CREATE INDEX IDX_D114B4F6A53A8AA ON line (provider_id)');
        $this->addSql('CREATE INDEX IDX_D114B4F6B6A263D9 ON line (import_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__operator AS SELECT name, email, url, phone, id, provider_id, import_id FROM operator');
        $this->addSql('DROP TABLE operator');
        $this->addSql('CREATE TABLE operator (name VARCHAR(255) NOT NULL, email VARCHAR(255) DEFAULT NULL, url VARCHAR(255) DEFAULT NULL, phone VARCHAR(255) DEFAULT NULL, id VARCHAR(255) NOT NULL, provider_id VARCHAR(255) DEFAULT NULL, import_id BLOB DEFAULT NULL --(DC2Type:uuid)
        , PRIMARY KEY (id), CONSTRAINT FK_D7A6A781A53A8AA FOREIGN KEY (provider_id) REFERENCES provider (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_D7A6A781B6A263D9 FOREIGN KEY (import_id) REFERENCES import (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO operator (name, email, url, phone, id, provider_id, import_id) SELECT name, email, url, phone, id, provider_id, import_id FROM __temp__operator');
        $this->addSql('DROP TABLE __temp__operator');
        $this->addSql('CREATE INDEX IDX_D7A6A781A53A8AA ON operator (provider_id)');
        $this->addSql('CREATE INDEX IDX_D7A6A781B6A263D9 ON operator (import_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__stop AS SELECT id, name, group_name, description, variant, latitude, longitude, on_demand, provider_id, import_id FROM stop');
        $this->addSql('DROP TABLE stop');
        $this->addSql('CREATE TABLE stop (id VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, group_name VARCHAR(255) DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, variant VARCHAR(255) DEFAULT NULL, latitude DOUBLE PRECISION DEFAULT NULL, longitude DOUBLE PRECISION DEFAULT NULL, on_demand BOOLEAN NOT NULL, provider_id VARCHAR(255) DEFAULT NULL, import_id BLOB DEFAULT NULL --(DC2Type:uuid)
        , PRIMARY KEY (id), CONSTRAINT FK_B95616B6A53A8AA FOREIGN KEY (provider_id) REFERENCES provider (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_B95616B6B6A263D9 FOREIGN KEY (import_id) REFERENCES import (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO stop (id, name, group_name, description, variant, latitude, longitude, on_demand, provider_id, import_id) SELECT id, name, group_name, description, variant, latitude, longitude, on_demand, provider_id, import_id FROM __temp__stop');
        $this->addSql('DROP TABLE __temp__stop');
        $this->addSql('CREATE INDEX IDX_B95616B6A53A8AA ON stop (provider_id)');
        $this->addSql('CREATE INDEX IDX_B95616B6B6A263D9 ON stop (import_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__track AS SELECT variant, description, id, line_id, final_id, provider_id, import_id FROM track');
        $this->addSql('DROP TABLE track');
        $this->addSql('CREATE TABLE track (variant VARCHAR(16) DEFAULT NULL, description VARCHAR(256) DEFAULT NULL, id VARCHAR(255) NOT NULL, line_id VARCHAR(255) DEFAULT NULL, final_id INTEGER DEFAULT NULL, provider_id VARCHAR(255) DEFAULT NULL, import_id BLOB DEFAULT NULL --(DC2Type:uuid)
        , PRIMARY KEY (id), CONSTRAINT FK_D6E3F8A64D7B7542 FOREIGN KEY (line_id) REFERENCES line (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_D6E3F8A613D41B2D FOREIGN KEY (final_id) REFERENCES track_stop (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_D6E3F8A6A53A8AA FOREIGN KEY (provider_id) REFERENCES provider (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_D6E3F8A6B6A263D9 FOREIGN KEY (import_id) REFERENCES import (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO track (variant, description, id, line_id, final_id, provider_id, import_id) SELECT variant, description, id, line_id, final_id, provider_id, import_id FROM __temp__track');
        $this->addSql('DROP TABLE __temp__track');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D6E3F8A613D41B2D ON track (final_id)');
        $this->addSql('CREATE INDEX IDX_D6E3F8A64D7B7542 ON track (line_id)');
        $this->addSql('CREATE INDEX IDX_D6E3F8A6A53A8AA ON track (provider_id)');
        $this->addSql('CREATE INDEX IDX_D6E3F8A6B6A263D9 ON track (import_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__track_stop AS SELECT id, sequence, stop_id, track_id, import_id FROM track_stop');
        $this->addSql('DROP TABLE track_stop');
        $this->addSql('CREATE TABLE track_stop (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, sequence INTEGER NOT NULL, stop_id VARCHAR(255) DEFAULT NULL, track_id VARCHAR(255) DEFAULT NULL, import_id BLOB DEFAULT NULL --(DC2Type:uuid)
        , CONSTRAINT FK_24003EB33902063D FOREIGN KEY (stop_id) REFERENCES stop (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_24003EB35ED23C43 FOREIGN KEY (track_id) REFERENCES track (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_24003EB3B6A263D9 FOREIGN KEY (import_id) REFERENCES import (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO track_stop (id, sequence, stop_id, track_id, import_id) SELECT id, sequence, stop_id, track_id, import_id FROM __temp__track_stop');
        $this->addSql('DROP TABLE __temp__track_stop');
        $this->addSql('CREATE INDEX IDX_24003EB33902063D ON track_stop (stop_id)');
        $this->addSql('CREATE INDEX IDX_24003EB35ED23C43 ON track_stop (track_id)');
        $this->addSql('CREATE INDEX IDX_24003EB3B6A263D9 ON track_stop (import_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__trip AS SELECT variant, note, id, operator_id, track_id, provider_id, import_id FROM trip');
        $this->addSql('DROP TABLE trip');
        $this->addSql('CREATE TABLE trip (variant VARCHAR(255) DEFAULT NULL, note VARCHAR(255) DEFAULT NULL, id VARCHAR(255) NOT NULL, operator_id VARCHAR(255) DEFAULT NULL, track_id VARCHAR(255) DEFAULT NULL, provider_id VARCHAR(255) DEFAULT NULL, import_id BLOB DEFAULT NULL --(DC2Type:uuid)
        , PRIMARY KEY (id), CONSTRAINT FK_7656F53B584598A3 FOREIGN KEY (operator_id) REFERENCES operator (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_7656F53B5ED23C43 FOREIGN KEY (track_id) REFERENCES track (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_7656F53BA53A8AA FOREIGN KEY (provider_id) REFERENCES provider (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_7656F53BB6A263D9 FOREIGN KEY (import_id) REFERENCES import (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO trip (variant, note, id, operator_id, track_id, provider_id, import_id) SELECT variant, note, id, operator_id, track_id, provider_id, import_id FROM __temp__trip');
        $this->addSql('DROP TABLE __temp__trip');
        $this->addSql('CREATE INDEX IDX_7656F53B584598A3 ON trip (operator_id)');
        $this->addSql('CREATE INDEX IDX_7656F53B5ED23C43 ON trip (track_id)');
        $this->addSql('CREATE INDEX IDX_7656F53BA53A8AA ON trip (provider_id)');
        $this->addSql('CREATE INDEX IDX_7656F53BB6A263D9 ON trip (import_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__trip_stop AS SELECT id, sequence, arrival, departure, stop_id, trip_id, import_id FROM trip_stop');
        $this->addSql('DROP TABLE trip_stop');
        $this->addSql('CREATE TABLE trip_stop (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, sequence INTEGER NOT NULL, arrival DATETIME NOT NULL, departure DATETIME NOT NULL, stop_id VARCHAR(255) DEFAULT NULL, trip_id VARCHAR(255) DEFAULT NULL, import_id BLOB DEFAULT NULL --(DC2Type:uuid)
        , CONSTRAINT FK_926E85DD3902063D FOREIGN KEY (stop_id) REFERENCES stop (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_926E85DDA5BC2E0E FOREIGN KEY (trip_id) REFERENCES trip (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_926E85DDB6A263D9 FOREIGN KEY (import_id) REFERENCES import (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO trip_stop (id, sequence, arrival, departure, stop_id, trip_id, import_id) SELECT id, sequence, arrival, departure, stop_id, trip_id, import_id FROM __temp__trip_stop');
        $this->addSql('DROP TABLE __temp__trip_stop');
        $this->addSql('CREATE INDEX IDX_926E85DD3902063D ON trip_stop (stop_id)');
        $this->addSql('CREATE INDEX IDX_926E85DDA5BC2E0E ON trip_stop (trip_id)');
        $this->addSql('CREATE INDEX IDX_926E85DDB6A263D9 ON trip_stop (import_id)');
    }
}
