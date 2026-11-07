<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261107090528 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add validity to tracks, track stops, trips and trip stops';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE track ADD valid_from DATETIME DEFAULT NULL, ADD valid_to DATETIME DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_D6E3F8A68C3213D0 ON track (valid_to)');
        $this->addSql('ALTER TABLE track_stop ADD valid_from DATETIME DEFAULT NULL, ADD valid_to DATETIME DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_24003EB38C3213D0 ON track_stop (valid_to)');
        $this->addSql('ALTER TABLE trip ADD valid_from DATETIME DEFAULT NULL, ADD valid_to DATETIME DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_7656F53B8C3213D0 ON trip (valid_to)');
        $this->addSql('ALTER TABLE trip_stop ADD valid_from DATETIME DEFAULT NULL, ADD valid_to DATETIME DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_926E85DD8C3213D0 ON trip_stop (valid_to)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_D6E3F8A68C3213D0 ON track');
        $this->addSql('ALTER TABLE track DROP valid_from, DROP valid_to');
        $this->addSql('DROP INDEX IDX_24003EB38C3213D0 ON track_stop');
        $this->addSql('ALTER TABLE track_stop DROP valid_from, DROP valid_to');
        $this->addSql('DROP INDEX IDX_7656F53B8C3213D0 ON trip');
        $this->addSql('ALTER TABLE trip DROP valid_from, DROP valid_to');
        $this->addSql('DROP INDEX IDX_926E85DD8C3213D0 ON trip_stop');
        $this->addSql('ALTER TABLE trip_stop DROP valid_from, DROP valid_to');
    }
}
