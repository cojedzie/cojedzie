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

import type { Departure, DepartureState, Line, Stop } from "@/model";
import { formatTime, Temporal } from "@/time";

// Stories of the timetable views: a Friday at Brama Wyżynna, seen at 09:40 in Warsaw.

export const timeZone = "Europe/Warsaw";
export const date = Temporal.PlainDate.from("2026-11-13");
export const now = Temporal.ZonedDateTime.from("2026-11-13T09:40[Europe/Warsaw]").toInstant();

const line = (id: string, symbol: string, type: Line["type"], night = false): Line => ({
    id,
    symbol,
    type,
    night,
    fast: false,
    $type: "vnd.cojedzie.line",
});

export const lines = {
    six: line("l6", "6", "tram"),
    eight: line("l8", "8", "tram"),
    bus: line("l111", "111", "bus"),
    night: line("lN1", "N1", "bus", true),
};

const stop: Stop = { id: "bw", name: "Brama Wyżynna", variant: "01", $type: "vnd.cojedzie.stop" };

/** A run at a wall clock time; `on` is its calendar day and `serviceDay` the day it belongs to, both today by default */
type Run = { line: Line; at: string; headsign: string; on?: string; serviceDay?: string; delay?: number };

function departure({ line, at, headsign, on, serviceDay, delay }: Run, live: boolean): Departure {
    const day = Temporal.PlainDate.from(serviceDay ?? date.toString());
    const scheduled = Temporal.ZonedDateTime.from(`${on ?? date}T${at}[${timeZone}]`).toInstant();
    const estimated = live && delay !== undefined ? scheduled.add({ seconds: delay }) : scheduled;
    const state = stateAt(estimated, delay, live);

    return {
        key: `${line.id}-${on ?? date}-${at}`,
        display: headsign,
        line,
        stop,
        scheduled,
        estimated,
        delay: state === "realtime" ? delay : 0,
        state,
        serviceDay: day,
    };
}

/** A run's state: all planned before the day starts; at 09:40 the gone ones departed, the ones with an estimate live */
function stateAt(estimated: Temporal.Instant, delay: number | undefined, live: boolean): DepartureState {
    if (!live) {
        return "planned";
    }

    if (Temporal.Instant.compare(estimated, now) < 0) {
        return "departed";
    }

    return delay === undefined ? "planned" : "realtime";
}

function every(line: Line, from: string, to: string, minutes: number, headsign: (index: number) => string): Run[] {
    const runs: Run[] = [];
    const end = Temporal.PlainTime.from(to);
    for (let time = Temporal.PlainTime.from(from), index = 0; Temporal.PlainTime.compare(time, end) <= 0; index++) {
        runs.push({ line, at: formatTime(time), headsign: headsign(index) });
        const next = time.add({ minutes });
        if (Temporal.PlainTime.compare(next, time) < 0) {
            break;
        }
        time = next;
    }
    return runs;
}

const runs: Run[] = [
    // a night line: the last runs of the previous service day leave after midnight, today
    { line: lines.night, at: "00:20", headsign: "Dworzec Główny", serviceDay: "2026-11-12" },
    { line: lines.night, at: "01:20", headsign: "Dworzec Główny", serviceDay: "2026-11-12" },
    // a tram with two headsigns: every third run is shortened to Siedlce
    ...every(lines.six, "04:55", "23:25", 15, index => (index % 3 === 2 ? "Siedlce" : "Łostowice")),
    ...every(lines.eight, "05:10", "22:50", 20, () => "Jelitkowo"),
    ...every(lines.bus, "05:32", "21:32", 30, () => "Oliwa PKP"),
    // after midnight tomorrow, still today's service day
    { line: lines.night, at: "00:20", headsign: "Dworzec Główny", on: "2026-11-14" },
];

// live estimates for the runs around now, in seconds
const delays = new Map([
    ["l6-09:10", 45],
    ["l6-09:40", 120],
    ["l6-09:55", -40],
    ["l8-09:50", 75],
    ["l111-10:02", 300],
]);

const withDelays = runs.map(run => ({ ...run, delay: delays.get(`${run.line.id}-${run.at}`) }));

/** The day as the timetable has it, before the day starts: every run planned */
export const plannedDay: Departure[] = withDelays.map(run => departure(run, false));

/** The day at 09:40: earlier runs departed, the ones around now with live estimates */
export const liveDay: Departure[] = withDelays.map(run => departure(run, true));

/** The runs leaving between two wall clock times (inclusive), e.g. the hour and a half around now */
export function between(departures: Departure[], from: string, to: string): Departure[] {
    const [start, end] = [Temporal.PlainTime.from(from), Temporal.PlainTime.from(to)];
    const time = (departure: Departure) => departure.scheduled.toZonedDateTimeISO(timeZone).toPlainTime();

    return departures.filter(
        departure =>
            Temporal.PlainTime.compare(time(departure), start) >= 0 &&
            Temporal.PlainTime.compare(time(departure), end) <= 0,
    );
}

/** The runs of one line */
export function ofLine(departures: Departure[], line: Line): Departure[] {
    return departures.filter(departure => departure.line.id === line.id);
}
