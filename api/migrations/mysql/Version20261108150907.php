<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Stores the service day of every trip. Existing trips get it from their id, which ends in the service date (ADR 0025);
 * trips whose id doesn't, written by a worker older than that, are deleted, the next import brings their dated
 * replacements. Trip stops go first, like the rollout migration of ADR 0025.
 */
final class Version20261108150907 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store the service day of every trip';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE trip ADD service_date DATE DEFAULT NULL');
        $this->addSql('DELETE trip_stop FROM trip_stop JOIN trip ON trip.id = trip_stop.trip_id WHERE trip.id NOT REGEXP \'[0-9]{4}-[0-9]{2}-[0-9]{2}$\'');
        $this->addSql('DELETE FROM trip WHERE id NOT REGEXP \'[0-9]{4}-[0-9]{2}-[0-9]{2}$\'');
        $this->addSql('UPDATE trip SET service_date = RIGHT(id, 10)');
        $this->addSql('ALTER TABLE trip MODIFY service_date DATE NOT NULL');
        $this->addSql('CREATE INDEX trip_service_date_idx ON trip (service_date)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX trip_service_date_idx ON trip');
        $this->addSql('ALTER TABLE trip DROP service_date');
    }
}
