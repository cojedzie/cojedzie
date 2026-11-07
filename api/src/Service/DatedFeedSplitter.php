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

namespace App\Service;

/**
 * Cuts the raw JSON of a dated feed, `{"<date>": {...}, "<date>": {...}}`, into the JSON of each day, while it is
 * being read in chunks. Only the day being read is held in memory.
 *
 * Days are cut at the keys of the top-level object, which are dates. Keys inside a day are field names, and dates
 * inside a day are values, followed by a comma or a closing bracket, never by a colon.
 */
final class DatedFeedSplitter
{
    private const string DATE_KEY = '/"(?<date>\d{4}-\d{2}-\d{2})"\s*:\s*/';

    /**
     * What was read and not cut into days yet: the beginning of the current day.
     */
    private string $unread = '';

    /**
     * Date of the day being read, null before the first date key.
     */
    private ?string $currentDate = null;

    /**
     * Adds a chunk of the feed and returns the days it completed.
     *
     * @return \Generator<string, string> JSON of each completed day, by date
     */
    public function feed(string $chunk): \Generator
    {
        $this->unread .= $chunk;

        while (($next = $this->nextDateKey()) !== null) {
            $day = $this->cutUntil($next);

            if ($this->currentDate !== null) {
                yield $this->currentDate => self::withoutTrailing($day, ',');
            }

            $this->currentDate = $next['date'];
        }
    }

    /**
     * Ends the feed and returns its last day, which is followed by the end of the top-level object.
     *
     * @return \Generator<string, string>
     */
    public function end(): \Generator
    {
        if ($this->currentDate !== null) {
            yield $this->currentDate => self::withoutTrailing($this->unread, '}');
        }

        $this->unread      = '';
        $this->currentDate = null;
    }

    /**
     * @return array{date: string, start: int, end: int}|null the next date key in the unread JSON
     */
    private function nextDateKey(): ?array
    {
        if (!preg_match(self::DATE_KEY, $this->unread, $match, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        [$key, $start] = $match[0];

        return [
            'date'  => $match['date'][0],
            'start' => $start,
            'end'   => $start + strlen($key),
        ];
    }

    /**
     * Takes the unread JSON before the date key and drops the key itself.
     *
     * @param array{start: int, end: int} $key
     */
    private function cutUntil(array $key): string
    {
        $before       = substr($this->unread, 0, $key['start']);
        $this->unread = substr($this->unread, $key['end']);

        return $before;
    }

    private static function withoutTrailing(string $json, string $separator): string
    {
        $json = rtrim($json);

        return str_ends_with($json, $separator) ? substr($json, 0, -strlen($separator)) : $json;
    }
}
