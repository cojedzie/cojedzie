<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220222211238 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Add import relation';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE import (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', started_at DATETIME NOT NULL, finished_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE line ADD import_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE line ADD CONSTRAINT FK_D114B4F6B6A263D9 FOREIGN KEY (import_id) REFERENCES import (id) ON DELETE RESTRICT');
        $this->addSql('CREATE INDEX IDX_D114B4F6B6A263D9 ON line (import_id)');

        $this->addSql('ALTER TABLE operator ADD import_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE operator ADD CONSTRAINT FK_D7A6A781B6A263D9 FOREIGN KEY (import_id) REFERENCES import (id) ON DELETE RESTRICT');
        $this->addSql('CREATE INDEX IDX_D7A6A781B6A263D9 ON operator (import_id)');

        $this->addSql('ALTER TABLE provider ADD import_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE provider ADD CONSTRAINT FK_92C4739CB6A263D9 FOREIGN KEY (import_id) REFERENCES import (id) ON DELETE RESTRICT');
        $this->addSql('CREATE INDEX IDX_92C4739CB6A263D9 ON provider (import_id)');

        $this->addSql('ALTER TABLE stop ADD import_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE stop ADD CONSTRAINT FK_B95616B6B6A263D9 FOREIGN KEY (import_id) REFERENCES import (id) ON DELETE RESTRICT');
        $this->addSql('CREATE INDEX IDX_B95616B6B6A263D9 ON stop (import_id)');

        $this->addSql('ALTER TABLE track ADD import_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE track ADD CONSTRAINT FK_D6E3F8A6B6A263D9 FOREIGN KEY (import_id) REFERENCES import (id) ON DELETE RESTRICT');
        $this->addSql('CREATE INDEX IDX_D6E3F8A6B6A263D9 ON track (import_id)');

        $this->addSql('ALTER TABLE track_stop ADD import_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE track_stop ADD CONSTRAINT FK_24003EB3B6A263D9 FOREIGN KEY (import_id) REFERENCES import (id) ON DELETE RESTRICT');
        $this->addSql('CREATE INDEX IDX_24003EB3B6A263D9 ON track_stop (import_id)');

        $this->addSql('ALTER TABLE trip ADD import_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE trip ADD CONSTRAINT FK_7656F53BB6A263D9 FOREIGN KEY (import_id) REFERENCES import (id) ON DELETE RESTRICT');
        $this->addSql('CREATE INDEX IDX_7656F53BB6A263D9 ON trip (import_id)');

        $this->addSql('ALTER TABLE trip_stop ADD import_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE trip_stop ADD CONSTRAINT FK_926E85DDB6A263D9 FOREIGN KEY (import_id) REFERENCES import (id) ON DELETE RESTRICT');
        $this->addSql('CREATE INDEX IDX_926E85DDB6A263D9 ON trip_stop (import_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE line DROP FOREIGN KEY FK_D114B4F6B6A263D9');
        $this->addSql('ALTER TABLE operator DROP FOREIGN KEY FK_D7A6A781B6A263D9');
        $this->addSql('ALTER TABLE provider DROP FOREIGN KEY FK_92C4739CB6A263D9');
        $this->addSql('ALTER TABLE stop DROP FOREIGN KEY FK_B95616B6B6A263D9');
        $this->addSql('ALTER TABLE track DROP FOREIGN KEY FK_D6E3F8A6B6A263D9');
        $this->addSql('ALTER TABLE track_stop DROP FOREIGN KEY FK_24003EB3B6A263D9');
        $this->addSql('ALTER TABLE trip DROP FOREIGN KEY FK_7656F53BB6A263D9');
        $this->addSql('ALTER TABLE trip_stop DROP FOREIGN KEY FK_926E85DDB6A263D9');

        $this->addSql('DROP TABLE import');

        $this->addSql('DROP INDEX IDX_D114B4F6B6A263D9 ON line');
        $this->addSql('ALTER TABLE line DROP import_id');

        $this->addSql('DROP INDEX IDX_D7A6A781B6A263D9 ON operator');
        $this->addSql('ALTER TABLE operator DROP import_id');

        $this->addSql('DROP INDEX IDX_92C4739CB6A263D9 ON provider');
        $this->addSql('ALTER TABLE provider DROP import_id');

        $this->addSql('DROP INDEX IDX_B95616B6B6A263D9 ON stop');
        $this->addSql('ALTER TABLE stop DROP import_id');

        $this->addSql('DROP INDEX IDX_D6E3F8A6B6A263D9 ON track');
        $this->addSql('ALTER TABLE track DROP import_id');

        $this->addSql('DROP INDEX IDX_24003EB3B6A263D9 ON track_stop');
        $this->addSql('ALTER TABLE track_stop DROP import_id');

        $this->addSql('DROP INDEX IDX_7656F53BB6A263D9 ON trip');
        $this->addSql('ALTER TABLE trip DROP import_id');

        $this->addSql('DROP INDEX IDX_926E85DDB6A263D9 ON trip_stop');
        $this->addSql('ALTER TABLE trip_stop DROP import_id');
    }
}
