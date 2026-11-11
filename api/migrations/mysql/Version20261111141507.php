<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Stores the origin of tracks, their first stop, next to their final stop, and fills it for the tracks imported
 * before: the stop with the lowest sequence.
 */
final class Version20261111141507 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store the origin of tracks';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE track ADD origin_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE track ADD CONSTRAINT FK_D6E3F8A656A273CC FOREIGN KEY (origin_id) REFERENCES track_stop (id) ON DELETE SET NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D6E3F8A656A273CC ON track (origin_id)');

        // tracks imported before have their first stop as the origin
        $this->addSql(<<<'SQL'
            UPDATE track
            JOIN (
                SELECT track_stop.track_id, MIN(track_stop.id) AS id
                FROM track_stop
                JOIN (SELECT track_id, MIN(sequence) AS sequence FROM track_stop GROUP BY track_id) lowest
                    ON lowest.track_id = track_stop.track_id AND lowest.sequence = track_stop.sequence
                GROUP BY track_stop.track_id
            ) origin ON origin.track_id = track.id
            SET track.origin_id = origin.id
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE track DROP FOREIGN KEY FK_D6E3F8A656A273CC');
        $this->addSql('DROP INDEX UNIQ_D6E3F8A656A273CC ON track');
        $this->addSql('ALTER TABLE track DROP origin_id');
    }
}
