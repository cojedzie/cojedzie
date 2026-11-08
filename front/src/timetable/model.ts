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

import type { Moment } from "moment";
import type { Departure, Line } from "@/model";

/** A wall clock time in the provider's time zone */
export type ClockTime = { hour: number; minute: number };

type Marked = {
    departure: Departure;
    departed: boolean;
    /** The service day (Y-m-d) of an entry that belongs to another day than the requested one */
    otherDay?: string;
};

export type TimetableListEntry = Marked & {
    planned: ClockTime;
    /** Only when the entry is realtime */
    estimated?: ClockTime;
    /** Seconds, only when the entry is realtime */
    delay?: number;
};

export type HourRow<TMinute> = { hour: number; minutes: TMinute[] };

export type LineMinute = Marked & {
    /** The planned minute */
    minute: number;
    /** Footnote letter of the headsign, only when the line has several at the stop */
    note?: string;
};

export type LineTimetable = {
    line: Line;
    rows: HourRow<LineMinute>[];
    /** Empty when the line has one headsign at the stop */
    legend: { note: string; headsign: string }[];
};

export type CombinedMinute = Marked & {
    minute: number;
    line: Line;
    headsign: string;
};

export type CombinedTimetable = HourRow<CombinedMinute>[];

/** The entries of the day in departure order. */
export function buildTimetableList(departures: Departure[], date: string, timeZone: string): TimetableListEntry[] {
    return sorted(departures).map(departure => {
        const realtime = departure.state === "realtime" && departure.estimated;

        return {
            ...mark(departure, date),
            planned: clock(departure.scheduled, timeZone),
            estimated: realtime ? clock(departure.estimated, timeZone) : undefined,
            delay: realtime ? departure.delay : undefined,
        };
    });
}

/** One table of hour rows per line, ordered by line symbol, like printed stop timetables. */
export function buildLineTimetables(departures: Departure[], date: string, timeZone: string): LineTimetable[] {
    const byLine = new Map<string, Departure[]>();
    for (const departure of sorted(departures)) {
        byLine.set(departure.line.id, [...(byLine.get(departure.line.id) ?? []), departure]);
    }

    return [...byLine.values()]
        .map(entries => {
            const headsigns = [...new Set(entries.map(entry => entry.display))];
            const legend =
                headsigns.length > 1 ? headsigns.map((headsign, index) => ({ note: letter(index), headsign })) : [];
            const notes = new Map(legend.map(({ note, headsign }) => [headsign, note]));

            return {
                line: entries[0].line,
                legend,
                rows: hourRows(entries, timeZone, (departure, minute) => ({
                    ...mark(departure, date),
                    minute,
                    note: notes.get(departure.display),
                })),
            };
        })
        .sort((a, b) => a.line.symbol.localeCompare(b.line.symbol, undefined, { numeric: true }));
}

/** One table of hour rows for the whole stop, each minute with its line and headsign. */
export function buildCombinedTimetable(departures: Departure[], date: string, timeZone: string): CombinedTimetable {
    return hourRows(sorted(departures), timeZone, (departure, minute) => ({
        ...mark(departure, date),
        minute,
        line: departure.line,
        headsign: departure.display,
    }));
}

/** Today's calendar date (Y-m-d) in the given time zone. */
export function todayIn(timeZone: string, now: Date = new Date()): string {
    // the Swedish locale writes dates as Y-m-d
    return new Intl.DateTimeFormat("sv-SE", { timeZone, year: "numeric", month: "2-digit", day: "2-digit" }).format(
        now,
    );
}

/** The first entry, in the order given, that hasn't left by `now`, judged by its estimate when it has one. */
export function nearestEntry<T extends { departure: Departure }>(entries: T[], now: Moment): T | undefined {
    return entries.find(({ departure }) => !(departure.estimated ?? departure.scheduled).isBefore(now));
}

function sorted(departures: Departure[]): Departure[] {
    return [...departures].sort((a, b) => a.scheduled.valueOf() - b.scheduled.valueOf());
}

function mark(departure: Departure, date: string): Marked {
    const otherDay = departure.serviceDay && departure.serviceDay !== date ? departure.serviceDay : undefined;

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
        const time = clock(departure.scheduled, timeZone);
        const last = rows.at(-1);

        if (last?.hour === time.hour) {
            last.minutes.push(minute(departure, time.minute));
        } else {
            rows.push({ hour: time.hour, minutes: [minute(departure, time.minute)] });
        }
    }

    return rows;
}

const formats = new Map<string, Intl.DateTimeFormat>();

function clock(time: Moment, timeZone: string): ClockTime {
    if (!formats.has(timeZone)) {
        formats.set(
            timeZone,
            new Intl.DateTimeFormat("en-GB", { timeZone, hour: "numeric", minute: "numeric", hourCycle: "h23" }),
        );
    }

    const parts = formats.get(timeZone).formatToParts(time.toDate());
    const part = (type: Intl.DateTimeFormatPartTypes) => Number(parts.find(p => p.type === type).value);

    return { hour: part("hour"), minute: part("minute") };
}

function letter(index: number): string {
    return String.fromCharCode("a".charCodeAt(0) + index);
}
