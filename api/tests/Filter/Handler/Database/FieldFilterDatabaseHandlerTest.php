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

namespace App\Tests\Filter\Handler\Database;

use App\Dto\ScheduledStop;
use App\Dto\Stop;
use App\Dto\TrackStop;
use App\Entity\StopEntity;
use App\Entity\TrackStopEntity;
use App\Entity\TripStopEntity;
use App\Event\HandleDatabaseRequirementEvent;
use App\Filter\Handler\Database\FieldFilterDatabaseHandler;
use App\Filter\Requirement\FieldFilter;
use App\Filter\Requirement\FieldFilterOperator;
use App\Provider\Repository;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Case-insensitive matching lowercases string fields only: Postgres has no LOWER() for timestamps or integers, and a
 * date compared as a lowercased string would skip its conversion to the database time zone.
 */
final class FieldFilterDatabaseHandlerTest extends KernelTestCase
{
    public function testStringFieldIsMatchedCaseInsensitively(): void
    {
        $builder = $this->filter(StopEntity::class, 'stop', Stop::class, new FieldFilter('name', 'Łostowice'));

        $this->assertStringContainsString('LOWER(stop.name) = :stop_name', $builder->getDQL());
        $this->assertSame('łostowice', $builder->getParameter(':stop_name')->getValue());
    }

    public function testTimestampFieldIsComparedAsDate(): void
    {
        $time    = Carbon::parse('2026-10-25 12:00', 'Europe/Warsaw');
        $builder = $this->filter(
            TripStopEntity::class,
            'trip_stop',
            ScheduledStop::class,
            new FieldFilter('departure', $time, FieldFilterOperator::GreaterOrEqual),
        );

        $this->assertStringContainsString('trip_stop.departure >= :trip_stop_departure', $builder->getDQL());
        $this->assertStringNotContainsString('LOWER', $builder->getDQL());
        $this->assertSame($time, $builder->getParameter(':trip_stop_departure')->getValue());
    }

    public function testIntegerFieldIsNotLowercased(): void
    {
        $builder = $this->filter(TrackStopEntity::class, 'track_stop', TrackStop::class, new FieldFilter('order', '3'));

        $this->assertStringContainsString('track_stop.order = :track_stop_order', $builder->getDQL());
        $this->assertStringNotContainsString('LOWER', $builder->getDQL());
    }

    private function filter(string $entity, string $alias, string $type, FieldFilter $filter): QueryBuilder
    {
        self::bootKernel();

        $builder = self::getContainer()->get(EntityManagerInterface::class)
            ->createQueryBuilder()
            ->select($alias)
            ->from($entity, $alias);

        new FieldFilterDatabaseHandler()->process(new HandleDatabaseRequirementEvent(
            $filter,
            $this->createStub(Repository::class),
            $builder,
            [
                'alias'  => $alias,
                'type'   => $type,
                'entity' => $entity,
            ],
        ));

        return $builder;
    }
}
