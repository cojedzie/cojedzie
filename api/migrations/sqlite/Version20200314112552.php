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
final class Version20200314112552 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('CREATE TEMPORARY TABLE __temp__trip_stop AS SELECT stop_id, trip_id, sequence, arrival, departure FROM trip_stop');
        $this->addSql('DROP TABLE trip_stop');
        $this->addSql('CREATE TABLE trip_stop (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, sequence INTEGER NOT NULL, arrival DATETIME NOT NULL, departure DATETIME NOT NULL, stop_id VARCHAR(255) DEFAULT NULL, trip_id VARCHAR(255) DEFAULT NULL)');
        $this->addSql('INSERT INTO trip_stop (stop_id, trip_id, sequence, arrival, departure) SELECT stop_id, trip_id, sequence, arrival, departure FROM __temp__trip_stop');
        $this->addSql('DROP TABLE __temp__trip_stop');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('CREATE TEMPORARY TABLE __temp__trip_stop AS SELECT sequence, arrival, departure, stop_id, trip_id FROM trip_stop');
        $this->addSql('DROP TABLE trip_stop');
        $this->addSql('CREATE TABLE trip_stop (sequence INTEGER NOT NULL, stop_id VARCHAR(255) NOT NULL COLLATE BINARY, trip_id VARCHAR(255) NOT NULL COLLATE BINARY, arrival DATETIME NOT NULL, departure DATETIME NOT NULL, PRIMARY KEY(stop_id, trip_id, sequence))');
        $this->addSql('INSERT INTO trip_stop (sequence, arrival, departure, stop_id, trip_id) SELECT sequence, arrival, departure, stop_id, trip_id FROM __temp__trip_stop');
        $this->addSql('DROP TABLE __temp__trip_stop');
    }
}
