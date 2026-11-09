<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds the kind of tracks (ADR 0031); existing tracks are side ones until the next import tells them apart.
 */
final class Version20261109150253 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the kind of tracks';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE track ADD kind VARCHAR(16) DEFAULT \'side\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE track DROP kind');
    }
}
