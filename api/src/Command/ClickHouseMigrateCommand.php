<?php
/*
 * Copyright (C) 2026 Kacper Donat
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

namespace App\Command;

use App\ClickHouse\ClickHouseConnection;
use App\ClickHouse\Migration;
use App\ClickHouse\Migrator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'clickhouse:migrate', description: 'Apply the ClickHouse migrations that haven\'t been applied yet.')]
class ClickHouseMigrateCommand extends Command
{
    public function __construct(
        private readonly ClickHouseConnection $connection,
        private readonly Migrator $migrator,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->connection->isEnabled()) {
            $io->error('There is no ClickHouse: COJEDZIE_CLICKHOUSE_URL is empty.');

            return Command::FAILURE;
        }

        $applied = $this->migrator->migrate();
        if ($applied === []) {
            $io->success('The ClickHouse schema is up to date.');

            return Command::SUCCESS;
        }

        $io->listing(array_map(fn (Migration $migration) => sprintf('%s: %s', Migrator::version($migration), $migration->getDescription()), $applied));
        $io->success(sprintf('Applied %d migration(s).', count($applied)));

        return Command::SUCCESS;
    }
}
