<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261107095002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the tasks of the import graph, with their progress, and the status of imports';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE import_task (id BINARY(16) NOT NULL, importer VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, status VARCHAR(16) NOT NULL, dependencies JSON NOT NULL, started_at DATETIME DEFAULT NULL, finished_at DATETIME DEFAULT NULL, error LONGTEXT DEFAULT NULL, progress INT DEFAULT 0 NOT NULL, progress_max INT DEFAULT NULL, progress_comment VARCHAR(255) DEFAULT NULL, import_id BINARY(16) NOT NULL, INDEX import_task_status_idx (import_id, status), INDEX IDX_BF5235A5B6A263D9 (import_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE import_task ADD CONSTRAINT FK_BF5235A5B6A263D9 FOREIGN KEY (import_id) REFERENCES import (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE import ADD status VARCHAR(16) DEFAULT NULL');
        // imports from before the graph set finished_at only once every importer ran; an unfinished one is
        // superseded by the next import
        $this->addSql("UPDATE import SET status = CASE WHEN finished_at IS NULL THEN 'running' ELSE 'succeeded' END");
        $this->addSql('ALTER TABLE import MODIFY status VARCHAR(16) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE import_task DROP FOREIGN KEY FK_BF5235A5B6A263D9');
        $this->addSql('DROP TABLE import_task');
        $this->addSql('ALTER TABLE import DROP status');
    }
}
