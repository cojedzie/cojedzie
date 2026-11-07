<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261107152301 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the jobs of import tasks and drop the payload of spawned tasks';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE import_job (id BLOB NOT NULL, status VARCHAR(16) NOT NULL, started_at DATETIME DEFAULT NULL, finished_at DATETIME DEFAULT NULL, error CLOB DEFAULT NULL, payload CLOB NOT NULL, task_id BLOB NOT NULL, PRIMARY KEY (id), CONSTRAINT FK_6FB540788DB60186 FOREIGN KEY (task_id) REFERENCES import_task (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX import_job_status_idx ON import_job (task_id, status)');
        $this->addSql('CREATE INDEX IDX_6FB540788DB60186 ON import_job (task_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__import_task AS SELECT id, importer, description, status, dependencies, started_at, finished_at, error, import_id FROM import_task');
        $this->addSql('DROP TABLE import_task');
        $this->addSql('CREATE TABLE import_task (id BLOB NOT NULL, importer VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, status VARCHAR(16) NOT NULL, dependencies CLOB NOT NULL, started_at DATETIME DEFAULT NULL, finished_at DATETIME DEFAULT NULL, error CLOB DEFAULT NULL, import_id BLOB NOT NULL, PRIMARY KEY (id), CONSTRAINT FK_BF5235A5B6A263D9 FOREIGN KEY (import_id) REFERENCES import (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO import_task (id, importer, description, status, dependencies, started_at, finished_at, error, import_id) SELECT id, importer, description, status, dependencies, started_at, finished_at, error, import_id FROM __temp__import_task');
        $this->addSql('DROP TABLE __temp__import_task');
        $this->addSql('CREATE INDEX IDX_BF5235A5B6A263D9 ON import_task (import_id)');
        $this->addSql('CREATE INDEX import_task_status_idx ON import_task (import_id, status)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE import_job');
        $this->addSql('ALTER TABLE import_task ADD COLUMN payload CLOB DEFAULT NULL');
    }
}
