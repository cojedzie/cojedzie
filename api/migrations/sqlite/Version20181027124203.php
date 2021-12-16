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
final class Version20181027124203 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('CREATE TEMPORARY TABLE __temp__provider AS SELECT id, name, class FROM provider');
        $this->addSql('DROP TABLE provider');
        $this->addSql('CREATE TABLE provider (id VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, class VARCHAR(255) NOT NULL, update_date DATETIME NOT NULL default CURRENT_TIMESTAMP, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO provider (id, name, class) SELECT id, name, class FROM __temp__provider');
        $this->addSql('DROP TABLE __temp__provider');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('CREATE TEMPORARY TABLE __temp__provider AS SELECT id, name, class FROM provider');
        $this->addSql('DROP TABLE provider');
        $this->addSql('CREATE TABLE provider (id VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, class VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO provider (id, name, class) SELECT id, name, class FROM __temp__provider');
        $this->addSql('DROP TABLE __temp__provider');
    }
}
