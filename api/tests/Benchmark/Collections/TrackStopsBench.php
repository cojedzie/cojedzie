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

namespace App\Tests\Benchmark\Collections;

use App\Service\IdUtils;
use Ds\Set;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Groups;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;

/**
 * The track stops step of the ZTM Gdańsk track import, with each collection flavour.
 *
 * Group `import` is the real import path, streaming JSON included. Group `import-pipeline` is a diagnostic: the
 * same pipeline over records decoded beforehand, so it shows the collection cost without parsing.
 *
 * phpbench reports the peak memory of the whole process, so the set-up resets it: the peak is the fixture kept in
 * memory plus the subject. The baseline subject of each group does no work, so its peak is the memory the subject
 * doesn't own: the variant's own memory is its peak minus the baseline's.
 */
final class TrackStopsBench
{
    private IdUtils $ids;
    private Set $existingStopIds;
    private array $records;

    public function setUp(): void
    {
        $this->ids             = new IdUtils();
        $this->existingStopIds = TrackStopsVariants::existingStopIds($this->ids);

        memory_reset_peak_usage();
    }

    public function setUpDecoded(): void
    {
        $this->records = TrackStopsVariants::decodedRecords();

        $this->setUp();
    }

    #[Groups(['import']), BeforeMethods('setUp'), Revs(1), Iterations(5), Warmup(1)]
    public function benchBaselineEndToEnd(): void
    {
        TrackStopsVariants::consume([]);
    }

    #[Groups(['import']), BeforeMethods('setUp'), Revs(1), Iterations(5), Warmup(1)]
    public function benchMaterializedEndToEnd(): void
    {
        TrackStopsVariants::consume(TrackStopsVariants::materialized(TrackStopsVariants::records(), $this->existingStopIds, $this->ids));
    }

    #[Groups(['import']), BeforeMethods('setUp'), Revs(1), Iterations(5), Warmup(1)]
    public function benchLazyEndToEnd(): void
    {
        TrackStopsVariants::consume(TrackStopsVariants::lazy(TrackStopsVariants::records(), $this->existingStopIds, $this->ids));
    }

    #[Groups(['import']), BeforeMethods('setUp'), Revs(1), Iterations(5), Warmup(1)]
    public function benchCompactEndToEnd(): void
    {
        TrackStopsVariants::consume(TrackStopsVariants::compact(TrackStopsVariants::records(), $this->existingStopIds, $this->ids));
    }

    #[Groups(['import']), BeforeMethods('setUp'), Revs(1), Iterations(5), Warmup(1)]
    public function benchCompactLazyEndToEnd(): void
    {
        TrackStopsVariants::consume(TrackStopsVariants::compactLazy(TrackStopsVariants::records(), $this->existingStopIds, $this->ids));
    }

    #[Groups(['import-pipeline']), BeforeMethods('setUpDecoded'), Revs(5), Iterations(10), Warmup(1)]
    public function benchBaselinePipeline(): void
    {
        TrackStopsVariants::consume([]);
    }

    #[Groups(['import-pipeline']), BeforeMethods('setUpDecoded'), Revs(5), Iterations(10), Warmup(1)]
    public function benchMaterializedPipeline(): void
    {
        TrackStopsVariants::consume(TrackStopsVariants::materialized($this->records, $this->existingStopIds, $this->ids));
    }

    #[Groups(['import-pipeline']), BeforeMethods('setUpDecoded'), Revs(5), Iterations(10), Warmup(1)]
    public function benchLazyPipeline(): void
    {
        TrackStopsVariants::consume(TrackStopsVariants::lazy($this->records, $this->existingStopIds, $this->ids));
    }
}
