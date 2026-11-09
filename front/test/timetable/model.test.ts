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

import { describe, expect, it } from "vitest";
import moment from "moment";
import { Temporal } from "@/calendar";
import type { Departure, DepartureState, Line } from "@/model";
import {
    buildCombinedTimetable,
    buildLineLists,
    buildLineTimetables,
    buildTimetableList,
    nearestEntry,
} from "@/timetable";

const zone = "Europe/Warsaw";
const date = Temporal.PlainDate.from("2026-11-13");

const line = (id: string, symbol: string): Line => ({
    id,
    symbol,
    type: "tram",
    night: false,
    fast: false,
    $type: "vnd.cojedzie.line",
});

const six = line("l6", "6");
const twelve = line("l12", "12");

type Options = {
    line?: Line;
    display?: string;
    serviceDay?: string;
    state?: DepartureState;
    estimated?: string;
};

// times are UTC, Warsaw is UTC+1 in November
function entry(scheduled: string, options: Options = {}): Departure {
    return {
        key: `${options.line?.id ?? six.id}-${scheduled}`,
        display: options.display ?? "Łostowice",
        line: options.line ?? six,
        stop: { id: "s", name: "Brama Wyżynna", $type: "vnd.cojedzie.stop" },
        scheduled: moment.utc(scheduled),
        estimated: options.estimated ? moment.utc(options.estimated) : null,
        delay: options.estimated ? moment.utc(options.estimated).diff(moment.utc(scheduled), "seconds") : null,
        serviceDay: Temporal.PlainDate.from(options.serviceDay ?? "2026-11-13"),
        state: options.state ?? "planned",
    };
}

describe("timetable list", () => {
    it("is empty for an empty day", () => {
        expect(buildTimetableList([], date, zone)).toEqual([]);
        expect(buildLineTimetables([], date, zone)).toEqual([]);
        expect(buildCombinedTimetable([], date, zone)).toEqual([]);
        expect(buildLineLists([], date, zone)).toEqual([]);
    });

    it("orders entries by planned time in the provider's time zone", () => {
        const list = buildTimetableList(
            [entry("2026-11-13T11:00:00Z"), entry("2026-11-12T23:30:00Z", { serviceDay: "2026-11-12" })],
            date,
            zone,
        );

        expect(list.map(e => e.planned)).toEqual([
            { hour: 0, minute: 30 },
            { hour: 12, minute: 0 },
        ]);
    });

    it("marks runs of the previous night and runs after midnight with their service day", () => {
        const list = buildTimetableList(
            [
                entry("2026-11-12T23:30:00Z", { serviceDay: "2026-11-12" }),
                entry("2026-11-13T11:00:00Z"),
                entry("2026-11-13T23:40:00Z", { serviceDay: "2026-11-13" }),
                entry("2026-11-14T00:30:00Z", { serviceDay: "2026-11-14" }),
            ],
            date,
            zone,
        );

        expect(list.map(e => e.otherDay?.toString())).toEqual(["2026-11-12", undefined, undefined, "2026-11-14"]);
    });

    it("shows the estimate and delay of realtime entries only", () => {
        const [departed, realtime, planned] = buildTimetableList(
            [
                entry("2026-11-13T10:00:00Z", { state: "departed", estimated: "2026-11-13T10:02:00Z" }),
                entry("2026-11-13T11:00:00Z", { state: "realtime", estimated: "2026-11-13T11:03:00Z" }),
                entry("2026-11-13T12:00:00Z"),
            ],
            date,
            zone,
        );

        expect(realtime).toMatchObject({ estimated: { hour: 12, minute: 3 }, delay: 180, departed: false });
        expect(departed).toMatchObject({ estimated: undefined, delay: undefined, departed: true });
        expect(planned).toMatchObject({ estimated: undefined, delay: undefined, departed: false });
    });
});

describe("timetable hour rows per line", () => {
    it("has one table per line, ordered by symbol, with hours in time order across midnight", () => {
        const tables = buildLineTimetables(
            [
                entry("2026-11-12T23:30:00Z", { serviceDay: "2026-11-12" }),
                entry("2026-11-13T05:10:00Z", { line: twelve }),
                entry("2026-11-13T05:05:00Z"),
                entry("2026-11-13T05:45:00Z"),
                entry("2026-11-13T23:40:00Z"),
            ],
            date,
            zone,
        );

        expect(tables.map(t => t.line.symbol)).toEqual(["6", "12"]);
        expect(tables[0].rows.map(row => [row.hour, row.minutes.map(m => m.minute)])).toEqual([
            [0, [30]],
            [6, [5, 45]],
            [0, [40]],
        ]);
    });

    it("has no footnotes when the line has one headsign", () => {
        const [table] = buildLineTimetables([entry("2026-11-13T05:05:00Z"), entry("2026-11-13T06:05:00Z")], date, zone);

        expect(table.legend).toEqual([]);
        expect(table.rows.flatMap(row => row.minutes.map(m => m.note))).toEqual([undefined, undefined]);
    });

    it("marks each minute with a letter of its headsign when the line has several", () => {
        const [table] = buildLineTimetables(
            [
                entry("2026-11-13T05:05:00Z", { display: "Łostowice" }),
                entry("2026-11-13T05:15:00Z", { display: "Zajezdnia" }),
                entry("2026-11-13T05:25:00Z", { display: "Łostowice" }),
            ],
            date,
            zone,
        );

        expect(table.legend).toEqual([
            { note: "a", headsign: "Łostowice" },
            { note: "b", headsign: "Zajezdnia" },
        ]);
        expect(table.rows[0].minutes.map(m => m.note)).toEqual(["a", "b", "a"]);
    });

    it("carries the departed flag and the other-day marker", () => {
        const [table] = buildLineTimetables(
            [entry("2026-11-12T23:30:00Z", { serviceDay: "2026-11-12", state: "departed" })],
            date,
            zone,
        );

        expect(table.rows[0].minutes[0]).toMatchObject({ departed: true });
        expect(table.rows[0].minutes[0].otherDay?.toString()).toBe("2026-11-12");
    });
});

describe("timetable list per line", () => {
    it("has one list per line, ordered by symbol, with entries in time order", () => {
        const lists = buildLineLists(
            [
                entry("2026-11-13T05:10:00Z", { line: twelve, display: "Oliwa" }),
                entry("2026-11-13T05:45:00Z"),
                entry("2026-11-13T05:05:00Z"),
            ],
            date,
            zone,
        );

        expect(lists.map(list => [list.line.symbol, list.headsign])).toEqual([
            ["6", "Łostowice"],
            ["12", "Oliwa"],
        ]);
        expect(lists[0].entries.map(e => e.planned)).toEqual([
            { hour: 6, minute: 5 },
            { hour: 6, minute: 45 },
        ]);
    });

    it("has no footnotes when the line has one headsign", () => {
        const [list] = buildLineLists([entry("2026-11-13T05:05:00Z"), entry("2026-11-13T06:05:00Z")], date, zone);

        expect(list.legend).toEqual([]);
        expect(list.entries.map(e => e.note)).toEqual([undefined, undefined]);
    });

    it("heads a line with several headsigns with its most frequent one", () => {
        const [list] = buildLineLists(
            [
                entry("2026-11-13T05:05:00Z", { display: "Zajezdnia" }),
                entry("2026-11-13T05:15:00Z", { display: "Łostowice" }),
                entry("2026-11-13T05:25:00Z", { display: "Łostowice" }),
            ],
            date,
            zone,
        );

        expect(list.headsign).toBe("Łostowice");
        expect(list.legend).toEqual([
            { note: "a", headsign: "Zajezdnia" },
            { note: "b", headsign: "Łostowice" },
        ]);
        expect(list.entries.map(e => e.note)).toEqual(["a", "b", "b"]);
    });

    it("gives entries the letters of the same line's hour rows", () => {
        const departures = [
            entry("2026-11-13T05:05:00Z", { display: "Łostowice" }),
            entry("2026-11-13T05:10:00Z", { line: twelve, display: "Oliwa" }),
            entry("2026-11-13T05:15:00Z", { display: "Zajezdnia" }),
            entry("2026-11-13T05:20:00Z", { line: twelve, display: "Jelitkowo" }),
            entry("2026-11-13T06:25:00Z", { display: "Łostowice" }),
        ];

        const lists = buildLineLists(departures, date, zone);
        const tables = buildLineTimetables(departures, date, zone);

        expect(lists.map(list => list.legend)).toEqual(tables.map(table => table.legend));
        expect(lists.map(list => list.entries.map(e => e.note))).toEqual(
            tables.map(table => table.rows.flatMap(row => row.minutes.map(m => m.note))),
        );
    });

    it("carries the estimate and delay of realtime entries, the departed flag and the other-day marker", () => {
        const [list] = buildLineLists(
            [
                entry("2026-11-12T23:30:00Z", { serviceDay: "2026-11-12", state: "departed" }),
                entry("2026-11-13T11:00:00Z", { state: "realtime", estimated: "2026-11-13T11:03:00Z" }),
            ],
            date,
            zone,
        );

        expect(list.entries[0]).toMatchObject({ departed: true, estimated: undefined });
        expect(list.entries[0].otherDay?.toString()).toBe("2026-11-12");
        expect(list.entries[1]).toMatchObject({
            departed: false,
            otherDay: undefined,
            estimated: { hour: 12, minute: 3 },
            delay: 180,
        });
    });
});

describe("combined timetable hour rows", () => {
    it("has the minutes of all lines in one table, each with its line and headsign", () => {
        const rows = buildCombinedTimetable(
            [
                entry("2026-11-13T05:05:00Z"),
                entry("2026-11-13T05:10:00Z", { line: twelve, display: "Stogi", state: "realtime" }),
                entry("2026-11-13T06:00:00Z", { serviceDay: "2026-11-12" }),
            ],
            date,
            zone,
        );

        expect(rows).toHaveLength(2);
        expect(rows[0]).toMatchObject({
            hour: 6,
            minutes: [
                { minute: 5, line: six, headsign: "Łostowice", departed: false },
                { minute: 10, line: twelve, headsign: "Stogi", departed: false },
            ],
        });
        expect(rows[1]).toMatchObject({ hour: 7, minutes: [{ minute: 0 }] });
        expect(rows[1].minutes[0].otherDay?.toString()).toBe("2026-11-12");
    });
});

describe("timetable helpers", () => {
    it("finds the first entry that hasn't left yet, by its estimate when it has one", () => {
        const list = buildTimetableList(
            [
                entry("2026-11-13T10:00:00Z"),
                entry("2026-11-13T10:10:00Z", { state: "realtime", estimated: "2026-11-13T10:20:00Z" }),
                entry("2026-11-13T10:15:00Z"),
            ],
            date,
            zone,
        );

        expect(nearestEntry(list, moment.utc("2026-11-13T09:00:00Z"))).toBe(list[0]);
        expect(nearestEntry(list, moment.utc("2026-11-13T10:16:00Z"))).toBe(list[1]);
        expect(nearestEntry(list, moment.utc("2026-11-13T10:21:00Z"))).toBeUndefined();
        expect(nearestEntry([], moment.utc("2026-11-13T11:00:00Z"))).toBeUndefined();
    });
});
