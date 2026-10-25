<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261025200000 extends AbstractMigration
{
    #[\Override]
    public function getDescription(): string
    {
        return 'Add import relation to provider, missed by Version20220222211238 on SQLite';
    }

    #[\Override]
    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof SqlitePlatform, 'Migration can only be executed safely on \'sqlite\'.');
        // databases created with doctrine:schema:create instead of migrations already have the column
        $this->skipIf($schema->getTable('provider')->hasColumn('import_id'), 'provider.import_id already exists.');

        $this->addSql('ALTER TABLE provider ADD COLUMN import_id BLOB DEFAULT NULL REFERENCES import (id) ON DELETE RESTRICT');
        $this->addSql('CREATE INDEX IDX_92C4739CB6A263D9 ON provider (import_id)');
    }

    #[\Override]
    public function down(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof SqlitePlatform, 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('DROP INDEX IDX_92C4739CB6A263D9');
        $this->addSql('ALTER TABLE provider DROP COLUMN import_id');
    }
}
