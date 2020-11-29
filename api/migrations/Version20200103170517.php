<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200103170517 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('DROP INDEX IDX_926E85DDA5BC2E0E');
        $this->addSql('DROP INDEX IDX_926E85DD3902063D');
        $this->addSql('CREATE TEMPORARY TABLE __temp__trip_stop AS SELECT stop_id, trip_id, sequence, arrival, departure FROM trip_stop');
        $this->addSql('DROP TABLE trip_stop');
        $this->addSql('CREATE TABLE trip_stop (stop_id VARCHAR(255) NOT NULL COLLATE BINARY, trip_id VARCHAR(255) NOT NULL COLLATE BINARY, sequence INTEGER NOT NULL, arrival DATETIME NOT NULL, departure DATETIME NOT NULL, PRIMARY KEY(stop_id, trip_id, sequence))');
        $this->addSql('INSERT INTO trip_stop (stop_id, trip_id, sequence, arrival, departure) SELECT stop_id, trip_id, sequence, arrival, departure FROM __temp__trip_stop');
        $this->addSql('DROP TABLE __temp__trip_stop');
        $this->addSql('DROP INDEX IDX_7656F53BA53A8AA');
        $this->addSql('DROP INDEX IDX_7656F53B5ED23C43');
        $this->addSql('DROP INDEX IDX_7656F53B584598A3');
        $this->addSql('CREATE TEMPORARY TABLE __temp__trip AS SELECT id, operator_id, track_id, provider_id, variant, note FROM trip');
        $this->addSql('DROP TABLE trip');
        $this->addSql('CREATE TABLE trip (id VARCHAR(255) NOT NULL COLLATE BINARY, operator_id VARCHAR(255) DEFAULT NULL COLLATE BINARY, track_id VARCHAR(255) DEFAULT NULL COLLATE BINARY, provider_id VARCHAR(255) DEFAULT NULL COLLATE BINARY, variant VARCHAR(255) DEFAULT NULL COLLATE BINARY, note VARCHAR(255) DEFAULT NULL COLLATE BINARY, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO trip (id, operator_id, track_id, provider_id, variant, note) SELECT id, operator_id, track_id, provider_id, variant, note FROM __temp__trip');
        $this->addSql('DROP TABLE __temp__trip');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('CREATE TEMPORARY TABLE __temp__trip AS SELECT id, variant, note, operator_id, track_id, provider_id FROM trip');
        $this->addSql('DROP TABLE trip');
        $this->addSql('CREATE TABLE trip (id VARCHAR(255) NOT NULL, variant VARCHAR(255) DEFAULT NULL, note VARCHAR(255) DEFAULT NULL, operator_id VARCHAR(255) DEFAULT NULL, track_id VARCHAR(255) DEFAULT NULL, provider_id VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO trip (id, variant, note, operator_id, track_id, provider_id) SELECT id, variant, note, operator_id, track_id, provider_id FROM __temp__trip');
        $this->addSql('DROP TABLE __temp__trip');
        $this->addSql('CREATE INDEX IDX_7656F53BA53A8AA ON trip (provider_id)');
        $this->addSql('CREATE INDEX IDX_7656F53B5ED23C43 ON trip (track_id)');
        $this->addSql('CREATE INDEX IDX_7656F53B584598A3 ON trip (operator_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__trip_stop AS SELECT sequence, stop_id, trip_id, arrival, departure FROM trip_stop');
        $this->addSql('DROP TABLE trip_stop');
        $this->addSql('CREATE TABLE trip_stop (sequence INTEGER NOT NULL, stop_id VARCHAR(255) NOT NULL, trip_id VARCHAR(255) NOT NULL, arrival DATETIME NOT NULL, departure DATETIME NOT NULL, PRIMARY KEY(stop_id, trip_id, sequence))');
        $this->addSql('INSERT INTO trip_stop (sequence, stop_id, trip_id, arrival, departure) SELECT sequence, stop_id, trip_id, arrival, departure FROM __temp__trip_stop');
        $this->addSql('DROP TABLE __temp__trip_stop');
        $this->addSql('CREATE INDEX IDX_926E85DDA5BC2E0E ON trip_stop (trip_id)');
        $this->addSql('CREATE INDEX IDX_926E85DD3902063D ON trip_stop (stop_id)');
    }
}
