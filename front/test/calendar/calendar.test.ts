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
import { buildMonth, clampDate, formatLongDate, formatMonth, formatShortDate, Temporal, todayIn } from "@/calendar";

const day = (date: string) => Temporal.PlainDate.from(date);

describe("calendar dates", () => {
    it("gives today's date in the provider's time zone", () => {
        expect(todayIn("Europe/Warsaw", Temporal.Instant.from("2026-11-13T23:30:00Z")).toString()).toBe("2026-11-14");
        expect(todayIn("Europe/Warsaw", Temporal.Instant.from("2026-11-13T22:30:00Z")).toString()).toBe("2026-11-13");
    });

    it("limits a date to a range open at either end", () => {
        const [min, max] = [day("2026-11-05"), day("2026-11-20")];

        expect(clampDate(day("2026-11-01"), min, max).toString()).toBe("2026-11-05");
        expect(clampDate(day("2026-11-25"), min, max).toString()).toBe("2026-11-20");
        expect(clampDate(day("2026-11-25"), min).toString()).toBe("2026-11-25");
    });

    it("names the day and the month in Polish", () => {
        expect(formatLongDate(day("2026-11-09"))).toBe("poniedziałek, 09.11.2026");
        expect(formatShortDate(day("2026-11-08"))).toBe("niedz. 08.11");
        expect(formatMonth(Temporal.PlainYearMonth.from("2026-11"))).toBe("listopad 2026");
    });
});

describe("calendar month", () => {
    it("lays the month out in six weeks from Monday, with the neighbouring months' days around it", () => {
        // November 2026 starts on a Sunday
        const { weeks } = buildMonth(Temporal.PlainYearMonth.from("2026-11"));

        expect(weeks).toHaveLength(6);
        expect(weeks.map(week => week[0].date.toString())).toEqual([
            "2026-10-26",
            "2026-11-02",
            "2026-11-09",
            "2026-11-16",
            "2026-11-23",
            "2026-11-30",
        ]);
        expect(weeks[0].map(cell => cell.inMonth)).toEqual([false, false, false, false, false, false, true]);
        expect(weeks[5][1]).toMatchObject({ inMonth: false });
        expect(weeks[5][1].date.toString()).toBe("2026-12-01");
    });

    it("marks today and the selected day, and disables the days out of range", () => {
        const days = buildMonth(Temporal.PlainYearMonth.from("2026-11"), {
            today: day("2026-11-09"),
            selected: day("2026-11-12"),
            min: day("2026-11-05"),
            max: day("2026-11-20"),
        }).weeks.flat();
        const cell = (date: string) => days.find(candidate => candidate.date.toString() === date);

        expect(cell("2026-11-09")).toMatchObject({ today: true, selected: false, disabled: false });
        expect(cell("2026-11-12")).toMatchObject({ today: false, selected: true });
        expect(["2026-11-04", "2026-11-05", "2026-11-20", "2026-11-21"].map(date => cell(date).disabled)).toEqual([
            true,
            false,
            false,
            true,
        ]);
    });
});
