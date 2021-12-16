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
final class Version20210423203451 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Create entities related to federated servers.';
    }

    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('CREATE TABLE federated_connection (id BLOB NOT NULL --(DC2Type:uuid)
        , server_id BLOB DEFAULT NULL --(DC2Type:uuid)
        , url VARCHAR(255) NOT NULL, opened_at DATETIME NOT NULL, closed_at DATETIME DEFAULT NULL, last_check DATETIME NOT NULL, next_check DATETIME NULL, failures INTEGER NOT NULL, failures_total INTEGER NOT NULL, state VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_D3AF7DF01844E6B7 ON federated_connection (server_id)');
        $this->addSql('CREATE TABLE federated_server (id BLOB NOT NULL --(DC2Type:uuid)
        , email VARCHAR(255) NOT NULL, maintainer VARCHAR(255) DEFAULT NULL, allowed_url VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('DROP TABLE federated_connection');
        $this->addSql('DROP TABLE federated_server');
    }
}
