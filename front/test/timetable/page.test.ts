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
import { Temporal } from "@/time";
import { timetableDateFromQuery, timetableDateQuery } from "@/timetable";

const today = Temporal.PlainDate.from("2026-11-10");
const range = { from: Temporal.PlainDate.from("2026-11-08"), to: Temporal.PlainDate.from("2026-11-22") };

describe("timetableDateFromQuery", () => {
    it("is today without a date", () => {
        expect(timetableDateFromQuery(undefined, today).toString()).toBe("2026-11-10");
    });

    it("is the date asked for", () => {
        expect(timetableDateFromQuery("2026-11-14", today).toString()).toBe("2026-11-14");
    });

    it("is today when the date isn't one", () => {
        expect(timetableDateFromQuery("jutro", today).toString()).toBe("2026-11-10");
        expect(timetableDateFromQuery("2026-02-30", today).toString()).toBe("2026-11-10");
        expect(timetableDateFromQuery(["2026-11-14", "2026-11-15"], today).toString()).toBe("2026-11-10");
    });

    it("keeps a date asked for to the days the timetable has", () => {
        expect(timetableDateFromQuery("2026-10-01", today, range).toString()).toBe("2026-11-08");
        expect(timetableDateFromQuery("2026-12-24", today, range).toString()).toBe("2026-11-22");
        expect(timetableDateFromQuery("2026-11-14", today, range).toString()).toBe("2026-11-14");
    });
});

describe("timetableDateQuery", () => {
    it("leaves today out, so a saved link shows the day it is opened on", () => {
        expect(timetableDateQuery(today, today)).toEqual({});
    });

    it("names any other day", () => {
        expect(timetableDateQuery(Temporal.PlainDate.from("2026-11-14"), today)).toEqual({ date: "2026-11-14" });
    });
});
