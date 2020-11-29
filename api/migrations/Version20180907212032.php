<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180907212032 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('CREATE TABLE track (id VARCHAR(255) NOT NULL, line_id VARCHAR(255) DEFAULT NULL, provider_id VARCHAR(255) DEFAULT NULL, variant VARCHAR(16) DEFAULT NULL, description VARCHAR(256) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_D6E3F8A64D7B7542 ON track (line_id)');
        $this->addSql('CREATE INDEX IDX_D6E3F8A6A53A8AA ON track (provider_id)');
        $this->addSql('CREATE TABLE track_stop (stop_id VARCHAR(255) NOT NULL, track_id VARCHAR(255) NOT NULL, sequence INTEGER NOT NULL, PRIMARY KEY(stop_id, track_id))');
        $this->addSql('CREATE INDEX IDX_24003EB33902063D ON track_stop (stop_id)');
        $this->addSql('CREATE INDEX IDX_24003EB35ED23C43 ON track_stop (track_id)');
        $this->addSql('CREATE TABLE operator (id VARCHAR(255) NOT NULL, provider_id VARCHAR(255) DEFAULT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) DEFAULT NULL, url VARCHAR(255) DEFAULT NULL, phone VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_D7A6A781A53A8AA ON operator (provider_id)');
        $this->addSql('CREATE TABLE provider (id VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, class VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE line (id VARCHAR(255) NOT NULL, operator_id VARCHAR(255) DEFAULT NULL, provider_id VARCHAR(255) DEFAULT NULL, symbol VARCHAR(16) NOT NULL, type VARCHAR(20) NOT NULL, fast BOOLEAN NOT NULL, night BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_D114B4F6584598A3 ON line (operator_id)');
        $this->addSql('CREATE INDEX IDX_D114B4F6A53A8AA ON line (provider_id)');
        $this->addSql('CREATE TABLE stop (id VARCHAR(255) NOT NULL, provider_id VARCHAR(255) DEFAULT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, variant VARCHAR(255) DEFAULT NULL, latitude DOUBLE PRECISION DEFAULT NULL, longitude DOUBLE PRECISION DEFAULT NULL, on_demand BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_B95616B6A53A8AA ON stop (provider_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('DROP TABLE track');
        $this->addSql('DROP TABLE track_stop');
        $this->addSql('DROP TABLE operator');
        $this->addSql('DROP TABLE provider');
        $this->addSql('DROP TABLE line');
        $this->addSql('DROP TABLE stop');
    }
}
