<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261107093329 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the ZTM Gdańsk schedule days imported per line';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE ztm_gdansk_schedule_day (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, date DATE NOT NULL, imported_at DATETIME NOT NULL, line_id VARCHAR(255) NOT NULL, CONSTRAINT FK_95DF77A24D7B7542 FOREIGN KEY (line_id) REFERENCES line (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX ztm_gdansk_schedule_day_line_date_idx ON ztm_gdansk_schedule_day (line_id, date)');
        $this->addSql('CREATE INDEX IDX_95DF77A24D7B7542 ON ztm_gdansk_schedule_day (line_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE ztm_gdansk_schedule_day');
    }
}
