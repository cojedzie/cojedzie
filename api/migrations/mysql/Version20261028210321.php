<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261028210321 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Bring the schema in line with the mapping on DBAL 4: drop DBAL 3 type comments';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE federated_connection CHANGE id id BINARY(16) NOT NULL, CHANGE server_id server_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE federated_server CHANGE id id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE import CHANGE id id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE line CHANGE import_id import_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE operator CHANGE import_id import_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE provider CHANGE import_id import_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE stop CHANGE import_id import_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE track CHANGE import_id import_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE track_stop CHANGE import_id import_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE trip CHANGE import_id import_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE trip_stop CHANGE import_id import_id BINARY(16) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE federated_connection CHANGE id id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', CHANGE server_id server_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE federated_server CHANGE id id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE import CHANGE id id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE line CHANGE import_id import_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE operator CHANGE import_id import_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE provider CHANGE import_id import_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE stop CHANGE import_id import_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE track CHANGE import_id import_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE track_stop CHANGE import_id import_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE trip CHANGE import_id import_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE trip_stop CHANGE import_id import_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\'');
    }
}
