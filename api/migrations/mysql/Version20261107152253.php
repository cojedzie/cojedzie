<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261107152253 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the jobs of import tasks and drop the payload of spawned tasks';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE import_job (id BINARY(16) NOT NULL, status VARCHAR(16) NOT NULL, started_at DATETIME DEFAULT NULL, finished_at DATETIME DEFAULT NULL, error LONGTEXT DEFAULT NULL, payload JSON NOT NULL, task_id BINARY(16) NOT NULL, INDEX import_job_status_idx (task_id, status), INDEX IDX_6FB540788DB60186 (task_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE import_job ADD CONSTRAINT FK_6FB540788DB60186 FOREIGN KEY (task_id) REFERENCES import_task (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE import_task DROP payload');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE import_job DROP FOREIGN KEY FK_6FB540788DB60186');
        $this->addSql('DROP TABLE import_job');
        $this->addSql('ALTER TABLE import_task ADD payload JSON DEFAULT NULL');
    }
}
