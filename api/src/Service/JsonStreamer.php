<?php
/*
 * Copyright (C) 2022 Kacper Donat
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

namespace App\Service;

use App\Parser\Exception\StreamOpenException;
use App\Parser\Exception\StreamReadException;
use App\Parser\FileStringStream;
use App\Parser\Json\BranchPathDecider;
use App\Parser\Json\JsonStreamingParser;
use App\Parser\Json\JsonStreamingTokenizer;
use App\Parser\TokenizedStream;

class JsonStreamer
{
    public function __construct(
        // bytes read from the feed at once
        private readonly int $readSize = 1 << 20,
    ) {
    }

    public function stream(string $url, string $branch): iterable
    {
        $stream = new TokenizedStream(
            new FileStringStream($url),
            new JsonStreamingTokenizer(),
        );

        $parser = new JsonStreamingParser(new BranchPathDecider($branch));

        return $parser($stream);
    }

    /**
     * Reads "<date>.<key>" rows of several dates of a dated feed, one day at a time.
     *
     * The streaming parser is far too slow for several days of the large dated feeds, and decoding a whole feed holds
     * all of its days in memory. So the feed is read in chunks, cut into days, and only the requested days are
     * decoded, each released before the next one. Dates absent from the feed are not yielded.
     *
     * @param string[] $dates
     * @return iterable<string, iterable<array>> rows of the key, by date, in the order of the feed
     */
    public function streamDated(string $url, string $key, array $dates): iterable
    {
        $requested = array_flip($dates);

        foreach ($this->days($url) as $date => $json) {
            if (isset($requested[$date])) {
                yield $date => $this->rowsOf($url, $json, $key);
            }
        }
    }

    /**
     * Raw JSON of each day of a dated feed, by date, in the order of the feed.
     *
     * @return \Generator<string, string>
     */
    private function days(string $url): \Generator
    {
        $splitter = new DatedFeedSplitter();

        foreach ($this->chunks($url) as $chunk) {
            yield from $splitter->feed($chunk);
        }

        yield from $splitter->end();
    }

    /**
     * Decodes a day once its rows are read. A generator keeps the value it yielded until it yields the next one, so
     * yielding decoded rows directly would keep the previous day in memory while the next one is decoded.
     *
     * @return \Generator<array>
     */
    private function rowsOf(string $url, string $json, string $key): \Generator
    {
        $day = $this->decode($url, $json);
        unset($json);

        $rows = $day[$key] ?? [];
        unset($day);

        yield from $rows;
    }

    /**
     * @return \Generator<string> the feed, in chunks of at most the read size
     */
    private function chunks(string $url): \Generator
    {
        $handle = $this->open($url);

        try {
            while (!feof($handle)) {
                yield $this->read($handle, $url);
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * @return resource
     */
    private function open(string $url)
    {
        $handle = @fopen($url, 'rb', false, stream_context_create(['http' => ['timeout' => 60.0]]));

        if ($handle === false) {
            throw new StreamOpenException(sprintf('Cannot open "%s": %s', $url, error_get_last()['message'] ?? 'unknown error'));
        }

        return $handle;
    }

    /**
     * @param resource $handle
     */
    private function read($handle, string $url): string
    {
        $chunk = fread($handle, $this->readSize);

        if ($chunk === false || stream_get_meta_data($handle)['timed_out']) {
            throw new StreamReadException(sprintf('Reading "%s" failed or timed out.', $url));
        }

        return $chunk;
    }

    private function decode(string $url, string $json): array
    {
        try {
            $value = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new StreamReadException(sprintf('"%s" is not valid JSON: %s', $url, $exception->getMessage()), previous: $exception);
        }

        return is_array($value) ? $value : [];
    }
}
