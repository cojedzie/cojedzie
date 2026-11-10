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

import { clockTimeIn, Temporal } from "@/time";
import type { Departure, Line } from "@/model";

type Marked = {
    departure: Departure;
    departed: boolean;
    /** The service day of an entry that belongs to another day than the requested one */
    otherDay?: Temporal.PlainDate;
};

export type TimetableListEntry = Marked & {
    /** Wall clock time in the provider's time zone */
    planned: Temporal.PlainTime;
    /** Only when the entry is realtime */
    estimated?: Temporal.PlainTime;
    /** Seconds, only when the entry is realtime */
    delay?: number;
};

/** Footnote letters of a line's headsigns other than its main one; empty when the line has one at the stop */
export type Legend = { note: string; headsign: string }[];

export type LineListEntry = TimetableListEntry & {
    /** Footnote letter of the headsign, only when it isn't the line's main one */
    note?: string;
};

export type LineList = {
    line: Line;
    /** The headsign of most of the line's entries */
    headsign: string;
    entries: LineListEntry[];
    legend: Legend;
};

export type HourRow<TMinute> = { hour: number; minutes: TMinute[] };

export type LineMinute = Marked & {
    /** The planned minute */
    minute: number;
    /** Footnote letter of the headsign, only when it isn't the line's main one */
    note?: string;
};

export type LineTimetable = {
    line: Line;
    /** The headsign of most of the line's entries */
    headsign: string;
    rows: HourRow<LineMinute>[];
    legend: Legend;
};

export type CombinedMinute = Marked & {
    minute: number;
    line: Line;
    headsign: string;
};

export type CombinedTimetable = HourRow<CombinedMinute>[];

/** The entries of the day in departure order. */
export function buildTimetableList(
    departures: Departure[],
    date: Temporal.PlainDate,
    timeZone: string,
): TimetableListEntry[] {
    return sorted(departures).map(departure => listEntry(departure, date, timeZone));
}

/** The entries of the day in departure order, one list per line ordered by line symbol. */
export function buildLineLists(departures: Departure[], date: Temporal.PlainDate, timeZone: string): LineList[] {
    return byLine(departures).map(({ line, headsign, entries, legend, notes }) => ({
        line,
        headsign,
        legend,
        entries: entries.map(departure => ({
            ...listEntry(departure, date, timeZone),
            note: notes.get(departure.display),
        })),
    }));
}

/** One table of hour rows per line, ordered by line symbol, like printed stop timetables. */
export function buildLineTimetables(
    departures: Departure[],
    date: Temporal.PlainDate,
    timeZone: string,
): LineTimetable[] {
    return byLine(departures).map(({ line, headsign, entries, legend, notes }) => ({
        line,
        headsign,
        legend,
        rows: hourRows(entries, timeZone, (departure, minute) => ({
            ...mark(departure, date),
            minute,
            note: notes.get(departure.display),
        })),
    }));
}

/** One table of hour rows for the whole stop, each minute with its line and headsign. */
export function buildCombinedTimetable(
    departures: Departure[],
    date: Temporal.PlainDate,
    timeZone: string,
): CombinedTimetable {
    return hourRows(sorted(departures), timeZone, (departure, minute) => ({
        ...mark(departure, date),
        minute,
        line: departure.line,
        headsign: departure.display,
    }));
}

/** The first entry, in the order given, that hasn't left by `now`, judged by its estimate when it has one. */
export function nearestEntry<T extends { departure: Departure }>(entries: T[], now: Temporal.Instant): T | undefined {
    return entries.find(
        ({ departure }) => Temporal.Instant.compare(departure.estimated ?? departure.scheduled, now) >= 0,
    );
}

function sorted(departures: Departure[]): Departure[] {
    return [...departures].sort((a, b) => Temporal.Instant.compare(a.scheduled, b.scheduled));
}

type LineEntries = {
    line: Line;
    /** The most frequent headsign, the line's heading; its entries carry no letter */
    headsign: string;
    /** In departure order */
    entries: Departure[];
    legend: Legend;
    /** Footnote letter by headsign */
    notes: Map<string, string>;
};

/**
 * Entries grouped by line, ordered by line symbol. The heading names a line's main headsign, so only its other
 * headsigns get letters.
 */
function byLine(departures: Departure[]): LineEntries[] {
    const groups = new Map<string, Departure[]>();
    for (const departure of sorted(departures)) {
        groups.set(departure.line.id, [...(groups.get(departure.line.id) ?? []), departure]);
    }

    return [...groups.values()]
        .map(entries => {
            const headsign = mostFrequent(entries.map(entry => entry.display));
            // letters in the order the other headsigns first run
            const others = [...new Set(entries.map(entry => entry.display))].filter(other => other !== headsign);
            const legend = others.map((other, index) => ({ note: letter(index), headsign: other }));

            return {
                line: entries[0].line,
                headsign,
                entries,
                legend,
                notes: new Map(legend.map(({ note, headsign }) => [headsign, note])),
            };
        })
        .sort((a, b) => a.line.symbol.localeCompare(b.line.symbol, undefined, { numeric: true }));
}

function listEntry(departure: Departure, date: Temporal.PlainDate, timeZone: string): TimetableListEntry {
    const realtime = departure.state === "realtime" && departure.estimated;

    return {
        ...mark(departure, date),
        planned: clockTimeIn(departure.scheduled, timeZone),
        estimated: realtime ? clockTimeIn(departure.estimated, timeZone) : undefined,
        delay: realtime ? departure.delay : undefined,
    };
}

/** The most frequent value; of equally frequent ones, the first given. */
function mostFrequent(values: string[]): string {
    const counts = new Map<string, number>();
    for (const value of values) {
        counts.set(value, (counts.get(value) ?? 0) + 1);
    }

    return [...counts.entries()].reduce((best, current) => (current[1] > best[1] ? current : best))[0];
}

function mark(departure: Departure, date: Temporal.PlainDate): Marked {
    const otherDay = departure.serviceDay && !departure.serviceDay.equals(date) ? departure.serviceDay : undefined;

    return { departure, departed: departure.state === "departed", otherDay };
}

/** Groups consecutive entries of the same hour, so the after-midnight runs of a day get rows of their own at its end. */
function hourRows<TMinute>(
    departures: Departure[],
    timeZone: string,
    minute: (departure: Departure, minute: number) => TMinute,
): HourRow<TMinute>[] {
    const rows: HourRow<TMinute>[] = [];

    for (const departure of departures) {
        const time = clockTimeIn(departure.scheduled, timeZone);
        const last = rows.at(-1);

        if (last?.hour === time.hour) {
            last.minutes.push(minute(departure, time.minute));
        } else {
            rows.push({ hour: time.hour, minutes: [minute(departure, time.minute)] });
        }
    }

    return rows;
}

function letter(index: number): string {
    return String.fromCharCode("a".charCodeAt(0) + index);
}
