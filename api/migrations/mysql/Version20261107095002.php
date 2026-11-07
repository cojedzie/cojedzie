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
        return 'Add the tasks of the import graph and whether an import succeeded';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE import_task (id BINARY(16) NOT NULL, importer VARCHAR(255) NOT NULL, payload JSON DEFAULT NULL, description VARCHAR(255) NOT NULL, status VARCHAR(16) NOT NULL, dependencies JSON NOT NULL, started_at DATETIME DEFAULT NULL, finished_at DATETIME DEFAULT NULL, error LONGTEXT DEFAULT NULL, import_id BINARY(16) NOT NULL, INDEX import_task_status_idx (import_id, status), INDEX IDX_BF5235A5B6A263D9 (import_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE import_task ADD CONSTRAINT FK_BF5235A5B6A263D9 FOREIGN KEY (import_id) REFERENCES import (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE import ADD succeeded TINYINT DEFAULT NULL, ADD check_in_id VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE import_task DROP FOREIGN KEY FK_BF5235A5B6A263D9');
        $this->addSql('DROP TABLE import_task');
        $this->addSql('ALTER TABLE import DROP succeeded, DROP check_in_id');
    }
}
