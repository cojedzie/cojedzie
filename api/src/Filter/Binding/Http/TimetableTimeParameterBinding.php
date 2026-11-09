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

namespace App\Filter\Binding\Http;

use App\Filter\Binding\Http\Exception\InvalidParameterException;
use App\Filter\Requirement\DayMode;
use App\Filter\Requirement\FieldFilter;
use App\Filter\Requirement\FieldFilterOperator;
use App\Filter\Requirement\TimetableDay;
use App\Utility\RequestUtils;
use Attribute;
use Carbon\Carbon;
use OpenApi\Attributes\Parameter;
use OpenApi\Attributes\Schema;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Time selection of a timetable: a day (`date`, `day`) or a range of departure times (`from`, `to`), ADR 0029.
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_PARAMETER)]
class TimetableTimeParameterBinding implements ParameterBinding
{
    /**
     * Accepted forms of `from` and `to`: ISO 8601 date-times with an offset, with or without fractions of seconds.
     * A literal + in a query string decodes to a space, so the offset is URL-encoded (`%2B01:00`) or `Z`.
     */
    private const array DATE_TIME_FORMATS = [\DATE_ATOM, 'Y-m-d\TH:i:s.uP'];

    #[\Override]
    public function getRequirementsFromRequest(Request $request): iterable
    {
        $query = $request->query;

        if ($query->has('from') || $query->has('to')) {
            yield from $this->range($request);

            return;
        }

        $day  = RequestUtils::getString($request, 'day');
        $mode = $day !== null ? DayMode::tryFrom($day) : DayMode::Calendar;

        if ($mode === null) {
            throw InvalidParameterException::invalid('day', sprintf(
                'expected one of %s',
                implode(', ', array_map(fn (DayMode $mode) => $mode->value, DayMode::cases()))
            ));
        }

        try {
            yield new TimetableDay(RequestUtils::getString($request, 'date'), $mode);
        } catch (\InvalidArgumentException) {
            throw InvalidParameterException::invalid('date', 'expected a date as YYYY-MM-DD');
        }
    }

    /**
     * @return iterable<FieldFilter>
     */
    private function range(Request $request): iterable
    {
        $query = $request->query;

        foreach (['date', 'day'] as $parameter) {
            if ($query->has($parameter)) {
                throw InvalidParameterException::invalid('from', sprintf('can\'t be combined with %s', $parameter));
            }
        }

        foreach (['from' => 'to', 'to' => 'from'] as $parameter => $other) {
            if (!$query->has($parameter)) {
                throw InvalidParameterException::invalid($parameter, sprintf('required together with %s', $other));
            }
        }

        $from = self::dateTime($request, 'from');
        $to   = self::dateTime($request, 'to');

        if ($to <= $from) {
            throw InvalidParameterException::invalid('to', 'must be later than from');
        }

        yield new FieldFilter('departure', $from, FieldFilterOperator::GreaterOrEqual);
        yield new FieldFilter('departure', $to, FieldFilterOperator::Less);
    }

    private static function dateTime(Request $request, string $parameter): Carbon
    {
        $value = RequestUtils::getString($request, $parameter) ?? '';

        foreach (self::DATE_TIME_FORMATS as $format) {
            $parsed = \DateTimeImmutable::createFromFormat('!' . $format, $value);

            // a value that only parses by overflowing (month 13, hour 25) is not a date-time
            if ($parsed !== false && \DateTimeImmutable::getLastErrors() === false) {
                return Carbon::instance($parsed)->utc();
            }
        }

        throw InvalidParameterException::invalid($parameter, 'expected an ISO 8601 date-time with an offset');
    }

    #[\Override]
    public function getDocumentation(Route $route): iterable
    {
        yield new Parameter(
            name: 'date',
            description: 'Day of the timetable, in the provider\'s time zone. Defaults to today. ' .
                'Can\'t be combined with from and to.',
            in: 'query',
            required: false,
            schema: new Schema(type: 'string', format: 'date'),
        );

        yield new Parameter(
            name: 'day',
            description: 'How the day is selected: calendar (departures between its midnights), ' .
                'service (trips of its service day, including their runs after midnight) or extended (both). ' .
                'Defaults to calendar.',
            in: 'query',
            required: false,
            schema: new Schema(
                type: 'string',
                default: DayMode::Calendar->value,
                enum: array_map(fn (DayMode $mode) => $mode->value, DayMode::cases()),
            ),
        );

        yield new Parameter(
            name: 'from',
            description: 'Start of a range of departure times, inclusive, as an ISO 8601 date-time with an offset. ' .
                'Requires to. The offset\'s + must be URL-encoded (%2B01:00), or use Z.',
            in: 'query',
            required: false,
            schema: new Schema(type: 'string', format: 'date-time'),
        );

        yield new Parameter(
            name: 'to',
            description: 'End of a range of departure times, exclusive, as an ISO 8601 date-time with an offset. ' .
                'Requires from. The offset\'s + must be URL-encoded (%2B01:00), or use Z.',
            in: 'query',
            required: false,
            schema: new Schema(type: 'string', format: 'date-time'),
        );
    }
}
