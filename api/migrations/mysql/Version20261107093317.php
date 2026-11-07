<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261107093317 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the ZTM Gdańsk schedule days imported per line';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE ztm_gdansk_schedule_day (id INT AUTO_INCREMENT NOT NULL, date DATE NOT NULL, imported_at DATETIME NOT NULL, line_id VARCHAR(255) NOT NULL, UNIQUE INDEX ztm_gdansk_schedule_day_line_date_idx (line_id, date), INDEX IDX_95DF77A24D7B7542 (line_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE ztm_gdansk_schedule_day ADD CONSTRAINT FK_95DF77A24D7B7542 FOREIGN KEY (line_id) REFERENCES line (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ztm_gdansk_schedule_day DROP FOREIGN KEY FK_95DF77A24D7B7542');
        $this->addSql('DROP TABLE ztm_gdansk_schedule_day');
    }
}
