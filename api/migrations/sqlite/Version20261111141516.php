<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Stores the origin of tracks, their first stop, next to their final stop, and fills it for the tracks imported
 * before: the stop with the lowest sequence.
 */
final class Version20261111141516 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store the origin of tracks';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TEMPORARY TABLE __temp__track AS SELECT id, line_id, provider_id, final_id, import_id, variant, description, valid_from, valid_to, kind FROM track');
        $this->addSql('DROP TABLE track');
        $this->addSql('CREATE TABLE track (id VARCHAR(255) NOT NULL, line_id VARCHAR(255) DEFAULT NULL, provider_id VARCHAR(255) DEFAULT NULL, final_id INTEGER DEFAULT NULL, import_id BLOB DEFAULT NULL, variant VARCHAR(16) DEFAULT NULL, description VARCHAR(256) DEFAULT NULL, valid_from DATETIME DEFAULT NULL, valid_to DATETIME DEFAULT NULL, kind VARCHAR(16) DEFAULT \'side\' NOT NULL, origin_id INTEGER DEFAULT NULL, PRIMARY KEY (id), CONSTRAINT FK_D6E3F8A64D7B7542 FOREIGN KEY (line_id) REFERENCES line (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_D6E3F8A613D41B2D FOREIGN KEY (final_id) REFERENCES track_stop (id) ON UPDATE NO ACTION ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_D6E3F8A6A53A8AA FOREIGN KEY (provider_id) REFERENCES provider (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_D6E3F8A6B6A263D9 FOREIGN KEY (import_id) REFERENCES import (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_D6E3F8A656A273CC FOREIGN KEY (origin_id) REFERENCES track_stop (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO track (id, line_id, provider_id, final_id, import_id, variant, description, valid_from, valid_to, kind) SELECT id, line_id, provider_id, final_id, import_id, variant, description, valid_from, valid_to, kind FROM __temp__track');
        $this->addSql('DROP TABLE __temp__track');
        $this->addSql('CREATE INDEX IDX_D6E3F8A68C3213D0 ON track (valid_to)');
        $this->addSql('CREATE INDEX IDX_D6E3F8A6B6A263D9 ON track (import_id)');
        $this->addSql('CREATE INDEX IDX_D6E3F8A6A53A8AA ON track (provider_id)');
        $this->addSql('CREATE INDEX IDX_D6E3F8A64D7B7542 ON track (line_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D6E3F8A613D41B2D ON track (final_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D6E3F8A656A273CC ON track (origin_id)');

        // tracks imported before have their first stop as the origin
        $this->addSql(<<<'SQL'
            UPDATE track
            SET origin_id = (
                SELECT track_stop.id
                FROM track_stop
                WHERE track_stop.track_id = track.id
                ORDER BY track_stop.sequence, track_stop.id
                LIMIT 1
            )
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TEMPORARY TABLE __temp__track AS SELECT variant, description, kind, id, valid_from, valid_to, line_id, final_id, provider_id, import_id FROM track');
        $this->addSql('DROP TABLE track');
        $this->addSql('CREATE TABLE track (variant VARCHAR(16) DEFAULT NULL, description VARCHAR(256) DEFAULT NULL, kind VARCHAR(16) DEFAULT \'side\' NOT NULL, id VARCHAR(255) NOT NULL, valid_from DATETIME DEFAULT NULL, valid_to DATETIME DEFAULT NULL, line_id VARCHAR(255) DEFAULT NULL, final_id INTEGER DEFAULT NULL, provider_id VARCHAR(255) DEFAULT NULL, import_id BLOB DEFAULT NULL, PRIMARY KEY (id), CONSTRAINT FK_D6E3F8A64D7B7542 FOREIGN KEY (line_id) REFERENCES line (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_D6E3F8A613D41B2D FOREIGN KEY (final_id) REFERENCES track_stop (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_D6E3F8A6A53A8AA FOREIGN KEY (provider_id) REFERENCES provider (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_D6E3F8A6B6A263D9 FOREIGN KEY (import_id) REFERENCES import (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO track (variant, description, kind, id, valid_from, valid_to, line_id, final_id, provider_id, import_id) SELECT variant, description, kind, id, valid_from, valid_to, line_id, final_id, provider_id, import_id FROM __temp__track');
        $this->addSql('DROP TABLE __temp__track');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D6E3F8A613D41B2D ON track (final_id)');
        $this->addSql('CREATE INDEX IDX_D6E3F8A68C3213D0 ON track (valid_to)');
        $this->addSql('CREATE INDEX IDX_D6E3F8A64D7B7542 ON track (line_id)');
        $this->addSql('CREATE INDEX IDX_D6E3F8A6A53A8AA ON track (provider_id)');
        $this->addSql('CREATE INDEX IDX_D6E3F8A6B6A263D9 ON track (import_id)');
    }
}
