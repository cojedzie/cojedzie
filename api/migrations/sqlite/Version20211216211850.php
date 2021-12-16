<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211216211850 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'SERIAL generation strategy for trip and track stops';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('DROP INDEX stop_in_track_idx');
        $this->addSql('DROP INDEX IDX_24003EB33902063D');
        $this->addSql('DROP INDEX IDX_24003EB35ED23C43');
        $this->addSql('CREATE TEMPORARY TABLE __temp__track_stop AS SELECT id, stop_id, track_id, sequence FROM track_stop');
        $this->addSql('DROP TABLE track_stop');
        $this->addSql('CREATE TABLE track_stop (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, stop_id VARCHAR(255) DEFAULT NULL COLLATE BINARY, track_id VARCHAR(255) DEFAULT NULL COLLATE BINARY, sequence INTEGER NOT NULL, CONSTRAINT FK_24003EB33902063D FOREIGN KEY (stop_id) REFERENCES stop (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_24003EB35ED23C43 FOREIGN KEY (track_id) REFERENCES track (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO track_stop (id, stop_id, track_id, sequence) SELECT id, stop_id, track_id, sequence FROM __temp__track_stop');
        $this->addSql('DROP TABLE __temp__track_stop');
        $this->addSql('CREATE UNIQUE INDEX stop_in_track_idx ON track_stop (stop_id, track_id, sequence)');
        $this->addSql('CREATE INDEX IDX_24003EB33902063D ON track_stop (stop_id)');
        $this->addSql('CREATE INDEX IDX_24003EB35ED23C43 ON track_stop (track_id)');
        $this->addSql('DROP INDEX IDX_926E85DD3902063D');
        $this->addSql('DROP INDEX IDX_926E85DDA5BC2E0E');
        $this->addSql('CREATE TEMPORARY TABLE __temp__trip_stop AS SELECT id, stop_id, trip_id, sequence, arrival, departure FROM trip_stop');
        $this->addSql('DROP TABLE trip_stop');
        $this->addSql('CREATE TABLE trip_stop (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, stop_id VARCHAR(255) DEFAULT NULL COLLATE BINARY, trip_id VARCHAR(255) DEFAULT NULL COLLATE BINARY, sequence INTEGER NOT NULL, arrival DATETIME NOT NULL, departure DATETIME NOT NULL, CONSTRAINT FK_926E85DD3902063D FOREIGN KEY (stop_id) REFERENCES stop (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_926E85DDA5BC2E0E FOREIGN KEY (trip_id) REFERENCES trip (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO trip_stop (id, stop_id, trip_id, sequence, arrival, departure) SELECT id, stop_id, trip_id, sequence, arrival, departure FROM __temp__trip_stop');
        $this->addSql('DROP TABLE __temp__trip_stop');
        $this->addSql('CREATE INDEX IDX_926E85DD3902063D ON trip_stop (stop_id)');
        $this->addSql('CREATE INDEX IDX_926E85DDA5BC2E0E ON trip_stop (trip_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('DROP INDEX IDX_24003EB33902063D');
        $this->addSql('DROP INDEX IDX_24003EB35ED23C43');
        $this->addSql('DROP INDEX stop_in_track_idx');
        $this->addSql('CREATE TEMPORARY TABLE __temp__track_stop AS SELECT id, stop_id, track_id, sequence FROM track_stop');
        $this->addSql('DROP TABLE track_stop');
        $this->addSql('CREATE TABLE track_stop (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, stop_id VARCHAR(255) DEFAULT NULL, track_id VARCHAR(255) DEFAULT NULL, sequence INTEGER NOT NULL)');
        $this->addSql('INSERT INTO track_stop (id, stop_id, track_id, sequence) SELECT id, stop_id, track_id, sequence FROM __temp__track_stop');
        $this->addSql('DROP TABLE __temp__track_stop');
        $this->addSql('CREATE INDEX IDX_24003EB33902063D ON track_stop (stop_id)');
        $this->addSql('CREATE INDEX IDX_24003EB35ED23C43 ON track_stop (track_id)');
        $this->addSql('CREATE UNIQUE INDEX stop_in_track_idx ON track_stop (stop_id, track_id, sequence)');
        $this->addSql('DROP INDEX IDX_926E85DD3902063D');
        $this->addSql('DROP INDEX IDX_926E85DDA5BC2E0E');
        $this->addSql('CREATE TEMPORARY TABLE __temp__trip_stop AS SELECT id, stop_id, trip_id, sequence, arrival, departure FROM trip_stop');
        $this->addSql('DROP TABLE trip_stop');
        $this->addSql('CREATE TABLE trip_stop (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, stop_id VARCHAR(255) DEFAULT NULL, trip_id VARCHAR(255) DEFAULT NULL, sequence INTEGER NOT NULL, arrival DATETIME NOT NULL, departure DATETIME NOT NULL)');
        $this->addSql('INSERT INTO trip_stop (id, stop_id, trip_id, sequence, arrival, departure) SELECT id, stop_id, trip_id, sequence, arrival, departure FROM __temp__trip_stop');
        $this->addSql('DROP TABLE __temp__trip_stop');
        $this->addSql('CREATE INDEX IDX_926E85DD3902063D ON trip_stop (stop_id)');
        $this->addSql('CREATE INDEX IDX_926E85DDA5BC2E0E ON trip_stop (trip_id)');
    }
}
