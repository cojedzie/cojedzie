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

import { Temporal } from "@/time";
import { describe, expect, it, vi } from "vitest";
import moment from "moment";
import { fetchTimetableDay, TIMETABLE_DAY_MODE, TIMETABLE_PAGE_SIZE } from "@/timetable";

const six = { id: "l6", symbol: "6", type: "tram", night: false, fast: false, $type: "vnd.cojedzie.line" };

const dto = (scheduled: string, line = "l6") => ({
    key: `${line}-${scheduled}`,
    display: "Łostowice",
    line: { id: line, $type: "vnd.cojedzie.line" },
    stop: { id: "s", $type: "vnd.cojedzie.stop" },
    scheduled,
    estimated: null,
    delay: null,
    serviceDay: "2026-11-13",
    state: "planned",
});

function client(pages: unknown[][]) {
    const get = vi.fn(async (endpoint: string, options: { query?: { offset?: number } }) => {
        if (endpoint === "v1_stop_tracks") {
            return { data: [{ order: 1, track: { id: "t", description: "", line: six } }] };
        }

        return { data: pages[(options.query?.offset ?? 0) / TIMETABLE_PAGE_SIZE] ?? [] };
    });

    return { get };
}

describe("fetching a timetable day", () => {
    it("asks for the day of a stop, optionally of one line, in the day mode", async () => {
        const api = client([[]]);

        await fetchTimetableDay(api as never, { stop: "s", line: "l6", date: Temporal.PlainDate.from("2026-11-13") });

        expect(api.get).toHaveBeenCalledWith("v1_stop_timetable", {
            version: "^1.0",
            params: { stop: "s" },
            query: { date: "2026-11-13", day: TIMETABLE_DAY_MODE, line: "l6", limit: TIMETABLE_PAGE_SIZE, offset: 0 },
        });
    });

    it("reads every page of the day", async () => {
        const full = Array.from({ length: TIMETABLE_PAGE_SIZE }, () => dto("2026-11-13T10:00:00+01:00"));
        const api = client([full, [dto("2026-11-13T23:00:00+01:00")]]);

        const day = await fetchTimetableDay(api as never, { stop: "s", date: Temporal.PlainDate.from("2026-11-13") });

        expect(day).toHaveLength(TIMETABLE_PAGE_SIZE + 1);
    });

    it("parses times and the service day, and fills in the lines from the stop's tracks", async () => {
        const api = client([[dto("2026-11-13T10:00:00+01:00"), dto("2026-11-13T11:00:00+01:00", "gone")]]);

        const [known, unknown] = await fetchTimetableDay(api as never, {
            stop: "s",
            date: Temporal.PlainDate.from("2026-11-13"),
        });

        expect(known.scheduled.isSame(moment.utc("2026-11-13T09:00:00Z"))).toBe(true);
        expect(known.estimated).toBeNull();
        expect(known.serviceDay?.toString()).toBe("2026-11-13");
        expect(known.line).toEqual(six);
        expect(unknown.line).toMatchObject({ id: "gone", symbol: "gone", type: "unknown" });
    });
});
