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

namespace App\Tests\Provider\Database;

use App\Dto\Departure;
use App\Dto\Stop;
use App\Entity\ProviderEntity;
use App\Filter\Requirement\RelatedFilter;
use App\Filter\Requirement\Requirement;
use App\Provider\Database\GenericTimetableRepository;
use App\Provider\TimetableRepository;
use App\Tests\Behat\Support\Provider\TestProvider;
use App\Tests\Behat\Support\TestData;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Ds\Seq;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Timetable entries read as planned departures.
 */
final class GenericTimetableRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private TestData $data;

    #[\Override]
    protected function setUp(): void
    {
        self::bootKernel();

        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->em->beginTransaction();

        $this->data = self::getContainer()->get(TestData::class);
        $this->data->stop('a', 'A');
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->em->rollback();

        parent::tearDown();
    }

    public function testDeparturesCarryLineDestinationAndServiceDay(): void
    {
        // a night run of the service day before, past midnight in the provider's timezone
        $this->data->trip('6', 'a', Carbon::parse('2026-11-14 00:40', 'Europe/Warsaw')->tz('UTC'), 'Zaspa', '2026-11-13');
        $this->data->flush();

        $departures = $this->departures();

        $this->assertCount(1, $departures);
        /** @var Departure $departure */
        $departure = $departures->first();
        $this->assertSame('2026-11-13', $departure->getTrip()->getServiceDay());
        $this->assertSame('Zaspa', $departure->getDisplay());
        $this->assertNull($departure->getEstimated());
        $this->assertSame('6', $departure->getLine()->getSymbol());
        $this->assertSame('a', $departure->getStop()->getId());
        $this->assertSame('2026-11-13', $departure->getServiceDay());
        $this->assertSame(
            '2026-11-14 00:40',
            $departure->getScheduled()->clone()->tz('Europe/Warsaw')->format('Y-m-d H:i')
        );
        $this->assertNotNull($departure->getKey());
    }

    public function testDeparturesAtTheSameTimeAreOrderedByLine(): void
    {
        $at = Carbon::parse('2026-11-14 10:00');
        $this->data->trip('8', 'a', $at, 'Jelitkowo');
        $this->data->trip('10', 'a', $at, 'Brzeźno');
        $this->data->trip('6', 'a', $at, 'Zaspa');
        $this->data->trip('4', 'a', $at->clone()->subMinute(), 'Oliwa');
        $this->data->flush();

        $this->assertSame(['4', '10', '6', '8'], $this->linesOf($this->departures()));
    }

    private function departures(Requirement ...$requirements): Seq
    {
        $provider = $this->em->getReference(ProviderEntity::class, TestProvider::IDENTIFIER);
        /** @var TimetableRepository $repository */
        $repository = self::getContainer()->get(GenericTimetableRepository::class)->withProvider($provider);

        // every trip also stops at its destination
        return $repository->departures(new RelatedFilter(Stop::reference('a'), Stop::class), ...$requirements);
    }

    /**
     * @return string[] symbols of the lines of the departures, in order
     */
    private function linesOf(Seq $departures): array
    {
        return $departures->map(fn (Departure $departure) => $departure->getLine()->getSymbol())->toArray();
    }
}
