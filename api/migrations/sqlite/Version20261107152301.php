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
        return 'Add the jobs of import tasks, with their progress';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE import_job (id BLOB NOT NULL, status VARCHAR(16) NOT NULL, started_at DATETIME DEFAULT NULL, finished_at DATETIME DEFAULT NULL, error CLOB DEFAULT NULL, progress INTEGER DEFAULT 0 NOT NULL, progress_max INTEGER DEFAULT NULL, progress_comment VARCHAR(255) DEFAULT NULL, payload CLOB NOT NULL, task_id BLOB NOT NULL, PRIMARY KEY (id), CONSTRAINT FK_6FB540788DB60186 FOREIGN KEY (task_id) REFERENCES import_task (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX import_job_status_idx ON import_job (task_id, status)');
        $this->addSql('CREATE INDEX IDX_6FB540788DB60186 ON import_job (task_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE import_job');
    }
}
