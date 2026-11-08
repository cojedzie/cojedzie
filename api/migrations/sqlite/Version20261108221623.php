<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Indexes the service days of each provider's trips, so the first and the last one are read from the index alone.
 */
final class Version20261108221623 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Index the service days of each provider\'s trips';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX trip_provider_service_date_idx ON trip (provider_id, service_date)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX trip_provider_service_date_idx');
    }
}
