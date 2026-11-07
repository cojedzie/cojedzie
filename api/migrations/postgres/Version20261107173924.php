<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Deletes the trips and tracks imported before they had validity. They have no end of validity, so they would be
 * valid on every day next to the dated trips and versioned tracks that replace them. Children go first, as SQLite
 * may not enforce the cascades.
 */
final class Version20261107173924 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Delete trips and tracks imported before they had validity';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DELETE FROM trip_stop USING trip WHERE trip.id = trip_stop.trip_id AND trip.valid_to IS NULL');
        $this->addSql('DELETE FROM trip WHERE valid_to IS NULL');
        // the final stop of a track is one of its stops
        $this->addSql('UPDATE track SET final_id = NULL WHERE valid_to IS NULL');
        $this->addSql('DELETE FROM track_stop USING track WHERE track.id = track_stop.track_id AND track.valid_to IS NULL');
        $this->addSql('DELETE FROM track WHERE valid_to IS NULL');
    }

    public function down(Schema $schema): void
    {
        // the deleted rows can't be restored, the next import brings their dated replacements
    }
}
