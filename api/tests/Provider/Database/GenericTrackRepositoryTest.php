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

use App\Dto\Track;
use App\Filter\Requirement\ValidityConstraint;
use App\Provider\TrackRepository;
use App\Tests\Behat\Support\Provider\TestProvider;
use App\Tests\Behat\Support\TestData;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Ds\Seq;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\MockClock;

/**
 * Several versions of a route are imported at once: tracks list the ones valid now, unless asked for another moment.
 */
final class GenericTrackRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    #[\Override]
    protected function setUp(): void
    {
        self::bootKernel();

        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->em->beginTransaction();

        self::getContainer()->get(MockClock::class)->modify('2026-10-25 12:00');

        $data = self::getContainer()->get(TestData::class);
        $data->stop('a', 'A');
        $data->stop('b', 'B');
        $data->track('5', ['a', 'b'], validTo: Carbon::parse('2026-10-25 11:00'));
        $data->track('6', ['a', 'b']);
        $data->track('7', ['a', 'b'], validFrom: Carbon::parse('2026-10-29 06:00'));
        $data->flush();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->em->rollback();

        parent::tearDown();
    }

    public function testListsTracksValidNowByDefault(): void
    {
        $this->assertSame(['6'], $this->linesOf($this->tracks()->all()));
    }

    public function testListsTracksValidAtTheRequestedMoment(): void
    {
        $inFourDays = new ValidityConstraint(Carbon::parse('2026-10-29 12:00'));

        $this->assertSame(['6', '7'], $this->linesOf($this->tracks()->all($inFourDays)));
    }

    private function tracks(): TrackRepository
    {
        return self::getContainer()->get(TestProvider::class)->getTrackRepository();
    }

    /**
     * @return string[] sorted symbols of the lines of the tracks
     */
    private function linesOf(Seq $tracks): array
    {
        return $tracks->map(fn (Track $track) => $track->getLine()->getSymbol())->sorted()->toArray();
    }
}
