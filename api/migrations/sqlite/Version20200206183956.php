<?php
/*
 * Copyright (C) 2021 Kacper Donat
 *
 * @author Kacper Donat <kacper@kadet.net>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200206183956 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('CREATE TEMPORARY TABLE __temp__track AS SELECT id, line_id, provider_id, variant, description FROM track');
        $this->addSql('DROP TABLE track');
        $this->addSql('CREATE TABLE track (id VARCHAR(255) NOT NULL COLLATE BINARY, line_id VARCHAR(255) DEFAULT NULL COLLATE BINARY, provider_id VARCHAR(255) DEFAULT NULL COLLATE BINARY, variant VARCHAR(16) DEFAULT NULL COLLATE BINARY, description VARCHAR(256) DEFAULT NULL COLLATE BINARY, final_id INTEGER DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO track (id, line_id, provider_id, variant, description) SELECT id, line_id, provider_id, variant, description FROM __temp__track');
        $this->addSql('DROP TABLE __temp__track');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D6E3F8A613D41B2D ON track (final_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__track_stop AS SELECT stop_id, track_id, sequence FROM track_stop');
        $this->addSql('DROP TABLE track_stop');
        $this->addSql('CREATE TABLE track_stop (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, sequence INTEGER NOT NULL, stop_id VARCHAR(255) DEFAULT NULL, track_id VARCHAR(255) DEFAULT NULL)');
        $this->addSql('INSERT INTO track_stop (stop_id, track_id, sequence) SELECT stop_id, track_id, sequence FROM __temp__track_stop');
        $this->addSql('DROP TABLE __temp__track_stop');
        $this->addSql('CREATE UNIQUE INDEX stop_in_track_idx ON track_stop (stop_id, track_id, sequence)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('DROP INDEX UNIQ_D6E3F8A613D41B2D');
        $this->addSql('CREATE TEMPORARY TABLE __temp__track AS SELECT id, variant, description, line_id, provider_id FROM track');
        $this->addSql('DROP TABLE track');
        $this->addSql('CREATE TABLE track (id VARCHAR(255) NOT NULL, variant VARCHAR(16) DEFAULT NULL, description VARCHAR(256) DEFAULT NULL, line_id VARCHAR(255) DEFAULT NULL, provider_id VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO track (id, variant, description, line_id, provider_id) SELECT id, variant, description, line_id, provider_id FROM __temp__track');
        $this->addSql('DROP TABLE __temp__track');
        $this->addSql('DROP INDEX stop_in_track_idx');
        $this->addSql('CREATE TEMPORARY TABLE __temp__track_stop AS SELECT sequence, stop_id, track_id FROM track_stop');
        $this->addSql('DROP TABLE track_stop');
        $this->addSql('CREATE TABLE track_stop (sequence INTEGER NOT NULL, stop_id VARCHAR(255) NOT NULL COLLATE BINARY, track_id VARCHAR(255) NOT NULL COLLATE BINARY, PRIMARY KEY(stop_id, track_id, sequence))');
        $this->addSql('INSERT INTO track_stop (sequence, stop_id, track_id) SELECT sequence, stop_id, track_id FROM __temp__track_stop');
        $this->addSql('DROP TABLE __temp__track_stop');
    }
}
