<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261107152254 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the jobs of import tasks, with their progress';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE import_job (id UUID NOT NULL, status VARCHAR(16) NOT NULL, started_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, finished_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, error TEXT DEFAULT NULL, progress INT DEFAULT 0 NOT NULL, progress_max INT DEFAULT NULL, progress_comment VARCHAR(255) DEFAULT NULL, payload JSON NOT NULL, task_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX import_job_status_idx ON import_job (task_id, status)');
        $this->addSql('CREATE INDEX IDX_6FB540788DB60186 ON import_job (task_id)');
        $this->addSql('ALTER TABLE import_job ADD CONSTRAINT FK_6FB540788DB60186 FOREIGN KEY (task_id) REFERENCES import_task (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE import_job DROP CONSTRAINT FK_6FB540788DB60186');
        $this->addSql('DROP TABLE import_job');
    }
}
