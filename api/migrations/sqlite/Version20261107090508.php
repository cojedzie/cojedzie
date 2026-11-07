<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261107090508 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add validity to tracks, track stops, trips and trip stops';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__track AS SELECT id, line_id, provider_id, final_id, import_id, variant, description FROM track');
        $this->addSql('DROP TABLE track');
        $this->addSql('CREATE TABLE track (id VARCHAR(255) NOT NULL, line_id VARCHAR(255) DEFAULT NULL, provider_id VARCHAR(255) DEFAULT NULL, final_id INTEGER DEFAULT NULL, import_id BLOB DEFAULT NULL, variant VARCHAR(16) DEFAULT NULL, description VARCHAR(256) DEFAULT NULL, valid_from DATETIME DEFAULT NULL, valid_to DATETIME DEFAULT NULL, PRIMARY KEY (id), CONSTRAINT FK_D6E3F8A64D7B7542 FOREIGN KEY (line_id) REFERENCES line (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_D6E3F8A613D41B2D FOREIGN KEY (final_id) REFERENCES track_stop (id) ON UPDATE NO ACTION ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_D6E3F8A6A53A8AA FOREIGN KEY (provider_id) REFERENCES provider (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_D6E3F8A6B6A263D9 FOREIGN KEY (import_id) REFERENCES import (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO track (id, line_id, provider_id, final_id, import_id, variant, description) SELECT id, line_id, provider_id, final_id, import_id, variant, description FROM __temp__track');
        $this->addSql('DROP TABLE __temp__track');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D6E3F8A613D41B2D ON track (final_id)');
        $this->addSql('CREATE INDEX IDX_D6E3F8A64D7B7542 ON track (line_id)');
        $this->addSql('CREATE INDEX IDX_D6E3F8A6A53A8AA ON track (provider_id)');
        $this->addSql('CREATE INDEX IDX_D6E3F8A6B6A263D9 ON track (import_id)');
        $this->addSql('CREATE INDEX IDX_D6E3F8A68C3213D0 ON track (valid_to)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__track_stop AS SELECT id, stop_id, track_id, import_id, sequence FROM track_stop');
        $this->addSql('DROP TABLE track_stop');
        $this->addSql('CREATE TABLE track_stop (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, stop_id VARCHAR(255) DEFAULT NULL, track_id VARCHAR(255) DEFAULT NULL, import_id BLOB DEFAULT NULL, sequence INTEGER NOT NULL, valid_from DATETIME DEFAULT NULL, valid_to DATETIME DEFAULT NULL, CONSTRAINT FK_24003EB33902063D FOREIGN KEY (stop_id) REFERENCES stop (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_24003EB35ED23C43 FOREIGN KEY (track_id) REFERENCES track (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_24003EB3B6A263D9 FOREIGN KEY (import_id) REFERENCES import (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO track_stop (id, stop_id, track_id, import_id, sequence) SELECT id, stop_id, track_id, import_id, sequence FROM __temp__track_stop');
        $this->addSql('DROP TABLE __temp__track_stop');
        $this->addSql('CREATE UNIQUE INDEX stop_in_track_idx ON track_stop (stop_id, track_id, sequence)');
        $this->addSql('CREATE INDEX IDX_24003EB33902063D ON track_stop (stop_id)');
        $this->addSql('CREATE INDEX IDX_24003EB35ED23C43 ON track_stop (track_id)');
        $this->addSql('CREATE INDEX IDX_24003EB3B6A263D9 ON track_stop (import_id)');
        $this->addSql('CREATE INDEX IDX_24003EB38C3213D0 ON track_stop (valid_to)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__trip AS SELECT id, operator_id, track_id, provider_id, import_id, variant, note FROM trip');
        $this->addSql('DROP TABLE trip');
        $this->addSql('CREATE TABLE trip (id VARCHAR(255) NOT NULL, operator_id VARCHAR(255) DEFAULT NULL, track_id VARCHAR(255) DEFAULT NULL, provider_id VARCHAR(255) DEFAULT NULL, import_id BLOB DEFAULT NULL, variant VARCHAR(255) DEFAULT NULL, note VARCHAR(255) DEFAULT NULL, valid_from DATETIME DEFAULT NULL, valid_to DATETIME DEFAULT NULL, PRIMARY KEY (id), CONSTRAINT FK_7656F53B584598A3 FOREIGN KEY (operator_id) REFERENCES operator (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_7656F53B5ED23C43 FOREIGN KEY (track_id) REFERENCES track (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_7656F53BA53A8AA FOREIGN KEY (provider_id) REFERENCES provider (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_7656F53BB6A263D9 FOREIGN KEY (import_id) REFERENCES import (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO trip (id, operator_id, track_id, provider_id, import_id, variant, note) SELECT id, operator_id, track_id, provider_id, import_id, variant, note FROM __temp__trip');
        $this->addSql('DROP TABLE __temp__trip');
        $this->addSql('CREATE INDEX IDX_7656F53B584598A3 ON trip (operator_id)');
        $this->addSql('CREATE INDEX IDX_7656F53B5ED23C43 ON trip (track_id)');
        $this->addSql('CREATE INDEX IDX_7656F53BA53A8AA ON trip (provider_id)');
        $this->addSql('CREATE INDEX IDX_7656F53BB6A263D9 ON trip (import_id)');
        $this->addSql('CREATE INDEX IDX_7656F53B8C3213D0 ON trip (valid_to)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__trip_stop AS SELECT id, stop_id, trip_id, import_id, sequence, arrival, departure FROM trip_stop');
        $this->addSql('DROP TABLE trip_stop');
        $this->addSql('CREATE TABLE trip_stop (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, stop_id VARCHAR(255) DEFAULT NULL, trip_id VARCHAR(255) DEFAULT NULL, import_id BLOB DEFAULT NULL, sequence INTEGER NOT NULL, arrival DATETIME NOT NULL, departure DATETIME NOT NULL, valid_from DATETIME DEFAULT NULL, valid_to DATETIME DEFAULT NULL, CONSTRAINT FK_926E85DD3902063D FOREIGN KEY (stop_id) REFERENCES stop (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_926E85DDA5BC2E0E FOREIGN KEY (trip_id) REFERENCES trip (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_926E85DDB6A263D9 FOREIGN KEY (import_id) REFERENCES import (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO trip_stop (id, stop_id, trip_id, import_id, sequence, arrival, departure) SELECT id, stop_id, trip_id, import_id, sequence, arrival, departure FROM __temp__trip_stop');
        $this->addSql('DROP TABLE __temp__trip_stop');
        $this->addSql('CREATE INDEX IDX_926E85DD3902063D ON trip_stop (stop_id)');
        $this->addSql('CREATE INDEX IDX_926E85DDA5BC2E0E ON trip_stop (trip_id)');
        $this->addSql('CREATE INDEX IDX_926E85DDB6A263D9 ON trip_stop (import_id)');
        $this->addSql('CREATE INDEX IDX_926E85DD8C3213D0 ON trip_stop (valid_to)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__track AS SELECT variant, description, id, line_id, final_id, provider_id, import_id FROM track');
        $this->addSql('DROP TABLE track');
        $this->addSql('CREATE TABLE track (variant VARCHAR(16) DEFAULT NULL, description VARCHAR(256) DEFAULT NULL, id VARCHAR(255) NOT NULL, line_id VARCHAR(255) DEFAULT NULL, final_id INTEGER DEFAULT NULL, provider_id VARCHAR(255) DEFAULT NULL, import_id BLOB DEFAULT NULL, PRIMARY KEY (id), CONSTRAINT FK_D6E3F8A64D7B7542 FOREIGN KEY (line_id) REFERENCES line (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_D6E3F8A613D41B2D FOREIGN KEY (final_id) REFERENCES track_stop (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_D6E3F8A6A53A8AA FOREIGN KEY (provider_id) REFERENCES provider (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_D6E3F8A6B6A263D9 FOREIGN KEY (import_id) REFERENCES import (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO track (variant, description, id, line_id, final_id, provider_id, import_id) SELECT variant, description, id, line_id, final_id, provider_id, import_id FROM __temp__track');
        $this->addSql('DROP TABLE __temp__track');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D6E3F8A613D41B2D ON track (final_id)');
        $this->addSql('CREATE INDEX IDX_D6E3F8A64D7B7542 ON track (line_id)');
        $this->addSql('CREATE INDEX IDX_D6E3F8A6A53A8AA ON track (provider_id)');
        $this->addSql('CREATE INDEX IDX_D6E3F8A6B6A263D9 ON track (import_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__track_stop AS SELECT id, sequence, stop_id, track_id, import_id FROM track_stop');
        $this->addSql('DROP TABLE track_stop');
        $this->addSql('CREATE TABLE track_stop (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, sequence INTEGER NOT NULL, stop_id VARCHAR(255) DEFAULT NULL, track_id VARCHAR(255) DEFAULT NULL, import_id BLOB DEFAULT NULL, CONSTRAINT FK_24003EB33902063D FOREIGN KEY (stop_id) REFERENCES stop (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_24003EB35ED23C43 FOREIGN KEY (track_id) REFERENCES track (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_24003EB3B6A263D9 FOREIGN KEY (import_id) REFERENCES import (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO track_stop (id, sequence, stop_id, track_id, import_id) SELECT id, sequence, stop_id, track_id, import_id FROM __temp__track_stop');
        $this->addSql('DROP TABLE __temp__track_stop');
        $this->addSql('CREATE UNIQUE INDEX stop_in_track_idx ON track_stop (stop_id, track_id, sequence)');
        $this->addSql('CREATE INDEX IDX_24003EB33902063D ON track_stop (stop_id)');
        $this->addSql('CREATE INDEX IDX_24003EB35ED23C43 ON track_stop (track_id)');
        $this->addSql('CREATE INDEX IDX_24003EB3B6A263D9 ON track_stop (import_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__trip AS SELECT variant, note, id, operator_id, track_id, provider_id, import_id FROM trip');
        $this->addSql('DROP TABLE trip');
        $this->addSql('CREATE TABLE trip (variant VARCHAR(255) DEFAULT NULL, note VARCHAR(255) DEFAULT NULL, id VARCHAR(255) NOT NULL, operator_id VARCHAR(255) DEFAULT NULL, track_id VARCHAR(255) DEFAULT NULL, provider_id VARCHAR(255) DEFAULT NULL, import_id BLOB DEFAULT NULL, PRIMARY KEY (id), CONSTRAINT FK_7656F53B584598A3 FOREIGN KEY (operator_id) REFERENCES operator (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_7656F53B5ED23C43 FOREIGN KEY (track_id) REFERENCES track (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_7656F53BA53A8AA FOREIGN KEY (provider_id) REFERENCES provider (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_7656F53BB6A263D9 FOREIGN KEY (import_id) REFERENCES import (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO trip (variant, note, id, operator_id, track_id, provider_id, import_id) SELECT variant, note, id, operator_id, track_id, provider_id, import_id FROM __temp__trip');
        $this->addSql('DROP TABLE __temp__trip');
        $this->addSql('CREATE INDEX IDX_7656F53B584598A3 ON trip (operator_id)');
        $this->addSql('CREATE INDEX IDX_7656F53B5ED23C43 ON trip (track_id)');
        $this->addSql('CREATE INDEX IDX_7656F53BA53A8AA ON trip (provider_id)');
        $this->addSql('CREATE INDEX IDX_7656F53BB6A263D9 ON trip (import_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__trip_stop AS SELECT id, sequence, arrival, departure, stop_id, trip_id, import_id FROM trip_stop');
        $this->addSql('DROP TABLE trip_stop');
        $this->addSql('CREATE TABLE trip_stop (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, sequence INTEGER NOT NULL, arrival DATETIME NOT NULL, departure DATETIME NOT NULL, stop_id VARCHAR(255) DEFAULT NULL, trip_id VARCHAR(255) DEFAULT NULL, import_id BLOB DEFAULT NULL, CONSTRAINT FK_926E85DD3902063D FOREIGN KEY (stop_id) REFERENCES stop (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_926E85DDA5BC2E0E FOREIGN KEY (trip_id) REFERENCES trip (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_926E85DDB6A263D9 FOREIGN KEY (import_id) REFERENCES import (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO trip_stop (id, sequence, arrival, departure, stop_id, trip_id, import_id) SELECT id, sequence, arrival, departure, stop_id, trip_id, import_id FROM __temp__trip_stop');
        $this->addSql('DROP TABLE __temp__trip_stop');
        $this->addSql('CREATE INDEX IDX_926E85DD3902063D ON trip_stop (stop_id)');
        $this->addSql('CREATE INDEX IDX_926E85DDA5BC2E0E ON trip_stop (trip_id)');
        $this->addSql('CREATE INDEX IDX_926E85DDB6A263D9 ON trip_stop (import_id)');
    }
}
