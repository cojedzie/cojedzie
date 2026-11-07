<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261107095003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the tasks of the import graph and whether an import succeeded';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE import_task (id UUID NOT NULL, importer VARCHAR(255) NOT NULL, payload JSON DEFAULT NULL, description VARCHAR(255) NOT NULL, status VARCHAR(16) NOT NULL, dependencies JSON NOT NULL, started_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, finished_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, error TEXT DEFAULT NULL, import_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX import_task_status_idx ON import_task (import_id, status)');
        $this->addSql('CREATE INDEX IDX_BF5235A5B6A263D9 ON import_task (import_id)');
        $this->addSql('ALTER TABLE import_task ADD CONSTRAINT FK_BF5235A5B6A263D9 FOREIGN KEY (import_id) REFERENCES import (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE import ADD succeeded BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE import ADD check_in_id VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE import_task DROP CONSTRAINT FK_BF5235A5B6A263D9');
        $this->addSql('DROP TABLE import_task');
        $this->addSql('ALTER TABLE import DROP succeeded');
        $this->addSql('ALTER TABLE import DROP check_in_id');
    }
}
