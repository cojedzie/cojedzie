<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Stores the origin of tracks, their first stop, next to their final stop, and fills it for the tracks imported
 * before: the stop with the lowest sequence.
 */
final class Version20261111141508 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store the origin of tracks';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE track ADD origin_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE track ADD CONSTRAINT FK_D6E3F8A656A273CC FOREIGN KEY (origin_id) REFERENCES track_stop (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D6E3F8A656A273CC ON track (origin_id)');

        // tracks imported before have their first stop as the origin
        $this->addSql(<<<'SQL'
            UPDATE track
            SET origin_id = origin.id
            FROM (
                SELECT DISTINCT ON (track_id) track_id, id
                FROM track_stop
                ORDER BY track_id, sequence, id
            ) origin
            WHERE origin.track_id = track.id
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE track DROP CONSTRAINT FK_D6E3F8A656A273CC');
        $this->addSql('DROP INDEX UNIQ_D6E3F8A656A273CC');
        $this->addSql('ALTER TABLE track DROP origin_id');
    }
}
