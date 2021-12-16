<?php declare(strict_types=1);
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

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190111212909 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('CREATE TABLE trip_stop (stop_id VARCHAR(255) NOT NULL, trip_id VARCHAR(255) NOT NULL, sequence INTEGER NOT NULL, arrival DATETIME NOT NULL, departure DATETIME NOT NULL, PRIMARY KEY(stop_id, trip_id, sequence))');
        $this->addSql('CREATE INDEX IDX_926E85DD3902063D ON trip_stop (stop_id)');
        $this->addSql('CREATE INDEX IDX_926E85DDA5BC2E0E ON trip_stop (trip_id)');
        $this->addSql('CREATE TABLE trip (id VARCHAR(255) NOT NULL, operator_id VARCHAR(255) DEFAULT NULL, track_id VARCHAR(255) DEFAULT NULL, provider_id VARCHAR(255) DEFAULT NULL, variant VARCHAR(255) DEFAULT NULL, note VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_7656F53B584598A3 ON trip (operator_id)');
        $this->addSql('CREATE INDEX IDX_7656F53B5ED23C43 ON trip (track_id)');
        $this->addSql('CREATE INDEX IDX_7656F53BA53A8AA ON trip (provider_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('DROP TABLE trip_stop');
        $this->addSql('DROP TABLE trip');
    }
}
