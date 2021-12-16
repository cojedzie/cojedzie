<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211216204515 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Initial schema';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE federated_connection (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', server_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', url VARCHAR(255) NOT NULL, opened_at DATETIME NOT NULL, closed_at DATETIME DEFAULT NULL, last_check DATETIME DEFAULT NULL, next_check DATETIME NOT NULL, failures INT NOT NULL, failures_total INT NOT NULL, state VARCHAR(255) NOT NULL, last_status LONGTEXT DEFAULT NULL, INDEX IDX_D3AF7DF01844E6B7 (server_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE federated_server (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', email VARCHAR(255) NOT NULL, maintainer VARCHAR(255) DEFAULT NULL, allowed_url VARCHAR(255) NOT NULL, secret VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE line (id VARCHAR(255) NOT NULL, operator_id VARCHAR(255) DEFAULT NULL, provider_id VARCHAR(255) DEFAULT NULL, symbol VARCHAR(16) NOT NULL, type VARCHAR(20) NOT NULL, fast TINYINT(1) NOT NULL, night TINYINT(1) NOT NULL, INDEX IDX_D114B4F6584598A3 (operator_id), INDEX IDX_D114B4F6A53A8AA (provider_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE operator (id VARCHAR(255) NOT NULL, provider_id VARCHAR(255) DEFAULT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) DEFAULT NULL, url VARCHAR(255) DEFAULT NULL, phone VARCHAR(255) DEFAULT NULL, INDEX IDX_D7A6A781A53A8AA (provider_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE provider (id VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, class VARCHAR(255) NOT NULL, update_date DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE stop (id VARCHAR(255) NOT NULL, provider_id VARCHAR(255) DEFAULT NULL, name VARCHAR(255) NOT NULL, group_name VARCHAR(255) DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, variant VARCHAR(255) DEFAULT NULL, latitude DOUBLE PRECISION DEFAULT NULL, longitude DOUBLE PRECISION DEFAULT NULL, on_demand TINYINT(1) NOT NULL, INDEX IDX_B95616B6A53A8AA (provider_id), INDEX group_idx (group_name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE track (id VARCHAR(255) NOT NULL, line_id VARCHAR(255) DEFAULT NULL, final_id INT DEFAULT NULL, provider_id VARCHAR(255) DEFAULT NULL, variant VARCHAR(16) DEFAULT NULL, description VARCHAR(256) DEFAULT NULL, INDEX IDX_D6E3F8A64D7B7542 (line_id), UNIQUE INDEX UNIQ_D6E3F8A613D41B2D (final_id), INDEX IDX_D6E3F8A6A53A8AA (provider_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE track_stop (id INT AUTO_INCREMENT NOT NULL, stop_id VARCHAR(255) DEFAULT NULL, track_id VARCHAR(255) DEFAULT NULL, sequence INT NOT NULL, INDEX IDX_24003EB33902063D (stop_id), INDEX IDX_24003EB35ED23C43 (track_id), UNIQUE INDEX stop_in_track_idx (stop_id, track_id, sequence), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE trip (id VARCHAR(255) NOT NULL, operator_id VARCHAR(255) DEFAULT NULL, track_id VARCHAR(255) DEFAULT NULL, provider_id VARCHAR(255) DEFAULT NULL, variant VARCHAR(255) DEFAULT NULL, note VARCHAR(255) DEFAULT NULL, INDEX IDX_7656F53B584598A3 (operator_id), INDEX IDX_7656F53B5ED23C43 (track_id), INDEX IDX_7656F53BA53A8AA (provider_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE trip_stop (id INT AUTO_INCREMENT NOT NULL, stop_id VARCHAR(255) DEFAULT NULL, trip_id VARCHAR(255) DEFAULT NULL, sequence INT NOT NULL, arrival DATETIME NOT NULL, departure DATETIME NOT NULL, INDEX IDX_926E85DD3902063D (stop_id), INDEX IDX_926E85DDA5BC2E0E (trip_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE federated_connection ADD CONSTRAINT FK_D3AF7DF01844E6B7 FOREIGN KEY (server_id) REFERENCES federated_server (id)');
        $this->addSql('ALTER TABLE line ADD CONSTRAINT FK_D114B4F6584598A3 FOREIGN KEY (operator_id) REFERENCES operator (id)');
        $this->addSql('ALTER TABLE line ADD CONSTRAINT FK_D114B4F6A53A8AA FOREIGN KEY (provider_id) REFERENCES provider (id)');
        $this->addSql('ALTER TABLE operator ADD CONSTRAINT FK_D7A6A781A53A8AA FOREIGN KEY (provider_id) REFERENCES provider (id)');
        $this->addSql('ALTER TABLE stop ADD CONSTRAINT FK_B95616B6A53A8AA FOREIGN KEY (provider_id) REFERENCES provider (id)');
        $this->addSql('ALTER TABLE track ADD CONSTRAINT FK_D6E3F8A64D7B7542 FOREIGN KEY (line_id) REFERENCES line (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE track ADD CONSTRAINT FK_D6E3F8A613D41B2D FOREIGN KEY (final_id) REFERENCES track_stop (id)');
        $this->addSql('ALTER TABLE track ADD CONSTRAINT FK_D6E3F8A6A53A8AA FOREIGN KEY (provider_id) REFERENCES provider (id)');
        $this->addSql('ALTER TABLE track_stop ADD CONSTRAINT FK_24003EB33902063D FOREIGN KEY (stop_id) REFERENCES stop (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE track_stop ADD CONSTRAINT FK_24003EB35ED23C43 FOREIGN KEY (track_id) REFERENCES track (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE trip ADD CONSTRAINT FK_7656F53B584598A3 FOREIGN KEY (operator_id) REFERENCES operator (id)');
        $this->addSql('ALTER TABLE trip ADD CONSTRAINT FK_7656F53B5ED23C43 FOREIGN KEY (track_id) REFERENCES track (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE trip ADD CONSTRAINT FK_7656F53BA53A8AA FOREIGN KEY (provider_id) REFERENCES provider (id)');
        $this->addSql('ALTER TABLE trip_stop ADD CONSTRAINT FK_926E85DD3902063D FOREIGN KEY (stop_id) REFERENCES stop (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE trip_stop ADD CONSTRAINT FK_926E85DDA5BC2E0E FOREIGN KEY (trip_id) REFERENCES trip (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE federated_connection DROP FOREIGN KEY FK_D3AF7DF01844E6B7');
        $this->addSql('ALTER TABLE track DROP FOREIGN KEY FK_D6E3F8A64D7B7542');
        $this->addSql('ALTER TABLE line DROP FOREIGN KEY FK_D114B4F6584598A3');
        $this->addSql('ALTER TABLE trip DROP FOREIGN KEY FK_7656F53B584598A3');
        $this->addSql('ALTER TABLE line DROP FOREIGN KEY FK_D114B4F6A53A8AA');
        $this->addSql('ALTER TABLE operator DROP FOREIGN KEY FK_D7A6A781A53A8AA');
        $this->addSql('ALTER TABLE stop DROP FOREIGN KEY FK_B95616B6A53A8AA');
        $this->addSql('ALTER TABLE track DROP FOREIGN KEY FK_D6E3F8A6A53A8AA');
        $this->addSql('ALTER TABLE trip DROP FOREIGN KEY FK_7656F53BA53A8AA');
        $this->addSql('ALTER TABLE track_stop DROP FOREIGN KEY FK_24003EB33902063D');
        $this->addSql('ALTER TABLE trip_stop DROP FOREIGN KEY FK_926E85DD3902063D');
        $this->addSql('ALTER TABLE track_stop DROP FOREIGN KEY FK_24003EB35ED23C43');
        $this->addSql('ALTER TABLE trip DROP FOREIGN KEY FK_7656F53B5ED23C43');
        $this->addSql('ALTER TABLE track DROP FOREIGN KEY FK_D6E3F8A613D41B2D');
        $this->addSql('ALTER TABLE trip_stop DROP FOREIGN KEY FK_926E85DDA5BC2E0E');
        $this->addSql('DROP TABLE federated_connection');
        $this->addSql('DROP TABLE federated_server');
        $this->addSql('DROP TABLE line');
        $this->addSql('DROP TABLE operator');
        $this->addSql('DROP TABLE provider');
        $this->addSql('DROP TABLE stop');
        $this->addSql('DROP TABLE track');
        $this->addSql('DROP TABLE track_stop');
        $this->addSql('DROP TABLE trip');
        $this->addSql('DROP TABLE trip_stop');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
