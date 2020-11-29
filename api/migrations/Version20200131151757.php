<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200131151757 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('CREATE TEMPORARY TABLE __temp__stop AS SELECT id, provider_id, name, description, variant, latitude, longitude, on_demand FROM stop');
        $this->addSql('DROP TABLE stop');
        $this->addSql('CREATE TABLE stop (id VARCHAR(255) NOT NULL COLLATE BINARY, provider_id VARCHAR(255) DEFAULT NULL COLLATE BINARY, name VARCHAR(255) NOT NULL COLLATE BINARY, description VARCHAR(255) DEFAULT NULL COLLATE BINARY, variant VARCHAR(255) DEFAULT NULL COLLATE BINARY, latitude DOUBLE PRECISION DEFAULT NULL, longitude DOUBLE PRECISION DEFAULT NULL, on_demand BOOLEAN NOT NULL, group_name VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO stop (id, provider_id, name, description, variant, latitude, longitude, on_demand) SELECT id, provider_id, name, description, variant, latitude, longitude, on_demand FROM __temp__stop');
        $this->addSql('DROP TABLE __temp__stop');
        $this->addSql('CREATE INDEX group_idx ON stop (group_name)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('DROP INDEX group_idx');
        $this->addSql('CREATE TEMPORARY TABLE __temp__stop AS SELECT id, name, description, variant, latitude, longitude, on_demand, provider_id FROM stop');
        $this->addSql('DROP TABLE stop');
        $this->addSql('CREATE TABLE stop (id VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, variant VARCHAR(255) DEFAULT NULL, latitude DOUBLE PRECISION DEFAULT NULL, longitude DOUBLE PRECISION DEFAULT NULL, on_demand BOOLEAN NOT NULL, provider_id VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO stop (id, name, description, variant, latitude, longitude, on_demand, provider_id) SELECT id, name, description, variant, latitude, longitude, on_demand, provider_id FROM __temp__stop');
        $this->addSql('DROP TABLE __temp__stop');
    }
}
