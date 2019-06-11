<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190611163333 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('DROP INDEX IDX_B95616B6A53A8AA');
        $this->addSql('CREATE TEMPORARY TABLE __temp__stop AS SELECT id, provider_id, name, description, variant, latitude, longitude, on_demand FROM stop');
        $this->addSql('DROP TABLE stop');
        $this->addSql('CREATE TABLE stop (id VARCHAR(255) NOT NULL COLLATE BINARY, provider_id VARCHAR(255) DEFAULT NULL COLLATE BINARY, name VARCHAR(255) NOT NULL COLLATE BINARY, description VARCHAR(255) DEFAULT NULL COLLATE BINARY, variant VARCHAR(255) DEFAULT NULL COLLATE BINARY, latitude DOUBLE PRECISION DEFAULT NULL, longitude DOUBLE PRECISION DEFAULT NULL, on_demand BOOLEAN NOT NULL, PRIMARY KEY(id), CONSTRAINT FK_B95616B6A53A8AA FOREIGN KEY (provider_id) REFERENCES provider (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO stop (id, provider_id, name, description, variant, latitude, longitude, on_demand) SELECT id, provider_id, name, description, variant, latitude, longitude, on_demand FROM __temp__stop');
        $this->addSql('DROP TABLE __temp__stop');
        $this->addSql('CREATE INDEX IDX_B95616B6A53A8AA ON stop (provider_id)');
        $this->addSql('DROP INDEX IDX_D6E3F8A64D7B7542');
        $this->addSql('DROP INDEX IDX_D6E3F8A6A53A8AA');
        $this->addSql('CREATE TEMPORARY TABLE __temp__track AS SELECT id, line_id, provider_id, variant, description FROM track');
        $this->addSql('DROP TABLE track');
        $this->addSql('CREATE TABLE track (id VARCHAR(255) NOT NULL COLLATE BINARY, line_id VARCHAR(255) DEFAULT NULL COLLATE BINARY, provider_id VARCHAR(255) DEFAULT NULL COLLATE BINARY, variant VARCHAR(16) DEFAULT NULL COLLATE BINARY, description VARCHAR(256) DEFAULT NULL COLLATE BINARY, PRIMARY KEY(id), CONSTRAINT FK_D6E3F8A64D7B7542 FOREIGN KEY (line_id) REFERENCES line (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_D6E3F8A6A53A8AA FOREIGN KEY (provider_id) REFERENCES provider (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO track (id, line_id, provider_id, variant, description) SELECT id, line_id, provider_id, variant, description FROM __temp__track');
        $this->addSql('DROP TABLE __temp__track');
        $this->addSql('CREATE INDEX IDX_D6E3F8A64D7B7542 ON track (line_id)');
        $this->addSql('CREATE INDEX IDX_D6E3F8A6A53A8AA ON track (provider_id)');
        $this->addSql('DROP INDEX IDX_24003EB35ED23C43');
        $this->addSql('DROP INDEX IDX_24003EB33902063D');
        $this->addSql('CREATE TEMPORARY TABLE __temp__track_stop AS SELECT stop_id, track_id, sequence FROM track_stop');
        $this->addSql('DROP TABLE track_stop');
        $this->addSql('CREATE TABLE track_stop (stop_id VARCHAR(255) NOT NULL COLLATE BINARY, track_id VARCHAR(255) NOT NULL COLLATE BINARY, sequence INTEGER NOT NULL, PRIMARY KEY(stop_id, track_id, sequence), CONSTRAINT FK_24003EB33902063D FOREIGN KEY (stop_id) REFERENCES stop (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_24003EB35ED23C43 FOREIGN KEY (track_id) REFERENCES track (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO track_stop (stop_id, track_id, sequence) SELECT stop_id, track_id, sequence FROM __temp__track_stop');
        $this->addSql('DROP TABLE __temp__track_stop');
        $this->addSql('CREATE INDEX IDX_24003EB35ED23C43 ON track_stop (track_id)');
        $this->addSql('CREATE INDEX IDX_24003EB33902063D ON track_stop (stop_id)');
        $this->addSql('DROP INDEX IDX_7656F53B584598A3');
        $this->addSql('DROP INDEX IDX_7656F53B5ED23C43');
        $this->addSql('DROP INDEX IDX_7656F53BA53A8AA');
        $this->addSql('CREATE TEMPORARY TABLE __temp__trip AS SELECT id, operator_id, track_id, provider_id, variant, note FROM trip');
        $this->addSql('DROP TABLE trip');
        $this->addSql('CREATE TABLE trip (id VARCHAR(255) NOT NULL COLLATE BINARY, operator_id VARCHAR(255) DEFAULT NULL COLLATE BINARY, track_id VARCHAR(255) DEFAULT NULL COLLATE BINARY, provider_id VARCHAR(255) DEFAULT NULL COLLATE BINARY, variant VARCHAR(255) DEFAULT NULL COLLATE BINARY, note VARCHAR(255) DEFAULT NULL COLLATE BINARY, PRIMARY KEY(id), CONSTRAINT FK_7656F53B584598A3 FOREIGN KEY (operator_id) REFERENCES operator (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_7656F53B5ED23C43 FOREIGN KEY (track_id) REFERENCES track (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_7656F53BA53A8AA FOREIGN KEY (provider_id) REFERENCES provider (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO trip (id, operator_id, track_id, provider_id, variant, note) SELECT id, operator_id, track_id, provider_id, variant, note FROM __temp__trip');
        $this->addSql('DROP TABLE __temp__trip');
        $this->addSql('CREATE INDEX IDX_7656F53B584598A3 ON trip (operator_id)');
        $this->addSql('CREATE INDEX IDX_7656F53B5ED23C43 ON trip (track_id)');
        $this->addSql('CREATE INDEX IDX_7656F53BA53A8AA ON trip (provider_id)');
        $this->addSql('DROP INDEX IDX_D7A6A781A53A8AA');
        $this->addSql('CREATE TEMPORARY TABLE __temp__operator AS SELECT id, provider_id, name, email, url, phone FROM operator');
        $this->addSql('DROP TABLE operator');
        $this->addSql('CREATE TABLE operator (id VARCHAR(255) NOT NULL COLLATE BINARY, provider_id VARCHAR(255) DEFAULT NULL COLLATE BINARY, name VARCHAR(255) NOT NULL COLLATE BINARY, email VARCHAR(255) DEFAULT NULL COLLATE BINARY, url VARCHAR(255) DEFAULT NULL COLLATE BINARY, phone VARCHAR(255) DEFAULT NULL COLLATE BINARY, PRIMARY KEY(id), CONSTRAINT FK_D7A6A781A53A8AA FOREIGN KEY (provider_id) REFERENCES provider (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO operator (id, provider_id, name, email, url, phone) SELECT id, provider_id, name, email, url, phone FROM __temp__operator');
        $this->addSql('DROP TABLE __temp__operator');
        $this->addSql('CREATE INDEX IDX_D7A6A781A53A8AA ON operator (provider_id)');
        $this->addSql('DROP INDEX IDX_926E85DD3902063D');
        $this->addSql('DROP INDEX IDX_926E85DDA5BC2E0E');
        $this->addSql('CREATE TEMPORARY TABLE __temp__trip_stop AS SELECT stop_id, trip_id, sequence, arrival, departure FROM trip_stop');
        $this->addSql('DROP TABLE trip_stop');
        $this->addSql('CREATE TABLE trip_stop (stop_id VARCHAR(255) NOT NULL COLLATE BINARY, trip_id VARCHAR(255) NOT NULL COLLATE BINARY, sequence INTEGER NOT NULL, arrival DATETIME NOT NULL, departure DATETIME NOT NULL, PRIMARY KEY(stop_id, trip_id, sequence), CONSTRAINT FK_926E85DD3902063D FOREIGN KEY (stop_id) REFERENCES stop (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_926E85DDA5BC2E0E FOREIGN KEY (trip_id) REFERENCES trip (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO trip_stop (stop_id, trip_id, sequence, arrival, departure) SELECT stop_id, trip_id, sequence, arrival, departure FROM __temp__trip_stop');
        $this->addSql('DROP TABLE __temp__trip_stop');
        $this->addSql('CREATE INDEX IDX_926E85DD3902063D ON trip_stop (stop_id)');
        $this->addSql('CREATE INDEX IDX_926E85DDA5BC2E0E ON trip_stop (trip_id)');
        $this->addSql('DROP INDEX IDX_D114B4F6584598A3');
        $this->addSql('DROP INDEX IDX_D114B4F6A53A8AA');
        $this->addSql('CREATE TEMPORARY TABLE __temp__line AS SELECT id, operator_id, provider_id, symbol, type, fast, night FROM line');
        $this->addSql('DROP TABLE line');
        $this->addSql('CREATE TABLE line (id VARCHAR(255) NOT NULL COLLATE BINARY, operator_id VARCHAR(255) DEFAULT NULL COLLATE BINARY, provider_id VARCHAR(255) DEFAULT NULL COLLATE BINARY, symbol VARCHAR(16) NOT NULL COLLATE BINARY, type VARCHAR(20) NOT NULL COLLATE BINARY, fast BOOLEAN NOT NULL, night BOOLEAN NOT NULL, PRIMARY KEY(id), CONSTRAINT FK_D114B4F6584598A3 FOREIGN KEY (operator_id) REFERENCES operator (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_D114B4F6A53A8AA FOREIGN KEY (provider_id) REFERENCES provider (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO line (id, operator_id, provider_id, symbol, type, fast, night) SELECT id, operator_id, provider_id, symbol, type, fast, night FROM __temp__line');
        $this->addSql('DROP TABLE __temp__line');
        $this->addSql('CREATE INDEX IDX_D114B4F6584598A3 ON line (operator_id)');
        $this->addSql('CREATE INDEX IDX_D114B4F6A53A8AA ON line (provider_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('DROP INDEX IDX_D114B4F6584598A3');
        $this->addSql('DROP INDEX IDX_D114B4F6A53A8AA');
        $this->addSql('CREATE TEMPORARY TABLE __temp__line AS SELECT id, operator_id, provider_id, symbol, type, fast, night FROM line');
        $this->addSql('DROP TABLE line');
        $this->addSql('CREATE TABLE line (id VARCHAR(255) NOT NULL, operator_id VARCHAR(255) DEFAULT NULL, provider_id VARCHAR(255) DEFAULT NULL, symbol VARCHAR(16) NOT NULL, type VARCHAR(20) NOT NULL, fast BOOLEAN NOT NULL, night BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO line (id, operator_id, provider_id, symbol, type, fast, night) SELECT id, operator_id, provider_id, symbol, type, fast, night FROM __temp__line');
        $this->addSql('DROP TABLE __temp__line');
        $this->addSql('CREATE INDEX IDX_D114B4F6584598A3 ON line (operator_id)');
        $this->addSql('CREATE INDEX IDX_D114B4F6A53A8AA ON line (provider_id)');
        $this->addSql('DROP INDEX IDX_D7A6A781A53A8AA');
        $this->addSql('CREATE TEMPORARY TABLE __temp__operator AS SELECT id, provider_id, name, email, url, phone FROM operator');
        $this->addSql('DROP TABLE operator');
        $this->addSql('CREATE TABLE operator (id VARCHAR(255) NOT NULL, provider_id VARCHAR(255) DEFAULT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) DEFAULT NULL, url VARCHAR(255) DEFAULT NULL, phone VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO operator (id, provider_id, name, email, url, phone) SELECT id, provider_id, name, email, url, phone FROM __temp__operator');
        $this->addSql('DROP TABLE __temp__operator');
        $this->addSql('CREATE INDEX IDX_D7A6A781A53A8AA ON operator (provider_id)');
        $this->addSql('DROP INDEX IDX_B95616B6A53A8AA');
        $this->addSql('CREATE TEMPORARY TABLE __temp__stop AS SELECT id, provider_id, name, description, variant, latitude, longitude, on_demand FROM stop');
        $this->addSql('DROP TABLE stop');
        $this->addSql('CREATE TABLE stop (id VARCHAR(255) NOT NULL, provider_id VARCHAR(255) DEFAULT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, variant VARCHAR(255) DEFAULT NULL, latitude DOUBLE PRECISION DEFAULT NULL, longitude DOUBLE PRECISION DEFAULT NULL, on_demand BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO stop (id, provider_id, name, description, variant, latitude, longitude, on_demand) SELECT id, provider_id, name, description, variant, latitude, longitude, on_demand FROM __temp__stop');
        $this->addSql('DROP TABLE __temp__stop');
        $this->addSql('CREATE INDEX IDX_B95616B6A53A8AA ON stop (provider_id)');
        $this->addSql('DROP INDEX IDX_D6E3F8A64D7B7542');
        $this->addSql('DROP INDEX IDX_D6E3F8A6A53A8AA');
        $this->addSql('CREATE TEMPORARY TABLE __temp__track AS SELECT id, line_id, provider_id, variant, description FROM track');
        $this->addSql('DROP TABLE track');
        $this->addSql('CREATE TABLE track (id VARCHAR(255) NOT NULL, line_id VARCHAR(255) DEFAULT NULL, provider_id VARCHAR(255) DEFAULT NULL, variant VARCHAR(16) DEFAULT NULL, description VARCHAR(256) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO track (id, line_id, provider_id, variant, description) SELECT id, line_id, provider_id, variant, description FROM __temp__track');
        $this->addSql('DROP TABLE __temp__track');
        $this->addSql('CREATE INDEX IDX_D6E3F8A64D7B7542 ON track (line_id)');
        $this->addSql('CREATE INDEX IDX_D6E3F8A6A53A8AA ON track (provider_id)');
        $this->addSql('DROP INDEX IDX_24003EB33902063D');
        $this->addSql('DROP INDEX IDX_24003EB35ED23C43');
        $this->addSql('CREATE TEMPORARY TABLE __temp__track_stop AS SELECT sequence, stop_id, track_id FROM track_stop');
        $this->addSql('DROP TABLE track_stop');
        $this->addSql('CREATE TABLE track_stop (sequence INTEGER NOT NULL, stop_id VARCHAR(255) NOT NULL, track_id VARCHAR(255) NOT NULL, PRIMARY KEY(stop_id, track_id, sequence))');
        $this->addSql('INSERT INTO track_stop (sequence, stop_id, track_id) SELECT sequence, stop_id, track_id FROM __temp__track_stop');
        $this->addSql('DROP TABLE __temp__track_stop');
        $this->addSql('CREATE INDEX IDX_24003EB33902063D ON track_stop (stop_id)');
        $this->addSql('CREATE INDEX IDX_24003EB35ED23C43 ON track_stop (track_id)');
        $this->addSql('DROP INDEX IDX_7656F53B584598A3');
        $this->addSql('DROP INDEX IDX_7656F53B5ED23C43');
        $this->addSql('DROP INDEX IDX_7656F53BA53A8AA');
        $this->addSql('CREATE TEMPORARY TABLE __temp__trip AS SELECT id, operator_id, track_id, provider_id, variant, note FROM trip');
        $this->addSql('DROP TABLE trip');
        $this->addSql('CREATE TABLE trip (id VARCHAR(255) NOT NULL, operator_id VARCHAR(255) DEFAULT NULL, track_id VARCHAR(255) DEFAULT NULL, provider_id VARCHAR(255) DEFAULT NULL, variant VARCHAR(255) DEFAULT NULL, note VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO trip (id, operator_id, track_id, provider_id, variant, note) SELECT id, operator_id, track_id, provider_id, variant, note FROM __temp__trip');
        $this->addSql('DROP TABLE __temp__trip');
        $this->addSql('CREATE INDEX IDX_7656F53B584598A3 ON trip (operator_id)');
        $this->addSql('CREATE INDEX IDX_7656F53B5ED23C43 ON trip (track_id)');
        $this->addSql('CREATE INDEX IDX_7656F53BA53A8AA ON trip (provider_id)');
        $this->addSql('DROP INDEX IDX_926E85DD3902063D');
        $this->addSql('DROP INDEX IDX_926E85DDA5BC2E0E');
        $this->addSql('CREATE TEMPORARY TABLE __temp__trip_stop AS SELECT sequence, stop_id, trip_id, arrival, departure FROM trip_stop');
        $this->addSql('DROP TABLE trip_stop');
        $this->addSql('CREATE TABLE trip_stop (sequence INTEGER NOT NULL, stop_id VARCHAR(255) NOT NULL, trip_id VARCHAR(255) NOT NULL, arrival DATETIME NOT NULL, departure DATETIME NOT NULL, PRIMARY KEY(stop_id, trip_id, sequence))');
        $this->addSql('INSERT INTO trip_stop (sequence, stop_id, trip_id, arrival, departure) SELECT sequence, stop_id, trip_id, arrival, departure FROM __temp__trip_stop');
        $this->addSql('DROP TABLE __temp__trip_stop');
        $this->addSql('CREATE INDEX IDX_926E85DD3902063D ON trip_stop (stop_id)');
        $this->addSql('CREATE INDEX IDX_926E85DDA5BC2E0E ON trip_stop (trip_id)');
    }
}
