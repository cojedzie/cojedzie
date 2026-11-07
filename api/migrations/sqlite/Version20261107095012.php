<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261107095012 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the tasks of the import graph and whether an import succeeded';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE import_task (id BLOB NOT NULL, importer VARCHAR(255) NOT NULL, payload CLOB DEFAULT NULL, description VARCHAR(255) NOT NULL, status VARCHAR(16) NOT NULL, dependencies CLOB NOT NULL, started_at DATETIME DEFAULT NULL, finished_at DATETIME DEFAULT NULL, error CLOB DEFAULT NULL, import_id BLOB NOT NULL, PRIMARY KEY (id), CONSTRAINT FK_BF5235A5B6A263D9 FOREIGN KEY (import_id) REFERENCES import (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX import_task_status_idx ON import_task (import_id, status)');
        $this->addSql('CREATE INDEX IDX_BF5235A5B6A263D9 ON import_task (import_id)');
        $this->addSql('ALTER TABLE import ADD COLUMN succeeded BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE import ADD COLUMN check_in_id VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE import_task');
        $this->addSql('CREATE TEMPORARY TABLE __temp__import AS SELECT id, started_at, finished_at FROM import');
        $this->addSql('DROP TABLE import');
        $this->addSql('CREATE TABLE import (id BLOB NOT NULL, started_at DATETIME NOT NULL, finished_at DATETIME DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('INSERT INTO import (id, started_at, finished_at) SELECT id, started_at, finished_at FROM __temp__import');
        $this->addSql('DROP TABLE __temp__import');
    }
}
