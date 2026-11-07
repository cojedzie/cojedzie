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
        return 'Add the schedule days imported per line';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE schedule_day (id INT AUTO_INCREMENT NOT NULL, date DATE NOT NULL, imported_at DATETIME NOT NULL, line_id VARCHAR(255) NOT NULL, import_id BINARY(16) NOT NULL, provider_id VARCHAR(255) DEFAULT NULL, UNIQUE INDEX schedule_day_line_date_idx (line_id, date), INDEX IDX_78696C9A4D7B7542 (line_id), INDEX IDX_78696C9AB6A263D9 (import_id), INDEX IDX_78696C9AA53A8AA (provider_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE schedule_day ADD CONSTRAINT FK_78696C9A4D7B7542 FOREIGN KEY (line_id) REFERENCES line (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE schedule_day ADD CONSTRAINT FK_78696C9AB6A263D9 FOREIGN KEY (import_id) REFERENCES import (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE schedule_day ADD CONSTRAINT FK_78696C9AA53A8AA FOREIGN KEY (provider_id) REFERENCES provider (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE schedule_day DROP FOREIGN KEY FK_78696C9A4D7B7542');
        $this->addSql('ALTER TABLE schedule_day DROP FOREIGN KEY FK_78696C9AB6A263D9');
        $this->addSql('ALTER TABLE schedule_day DROP FOREIGN KEY FK_78696C9AA53A8AA');
        $this->addSql('DROP TABLE schedule_day');
    }
}
