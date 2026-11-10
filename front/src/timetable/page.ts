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

import { clampDate, Temporal } from "@/time";
import type { TimetableRange } from "@/model";

/**
 * The day a timetable page shows, from its `?date=` (Y-m-d), kept to the days the timetable has; without one, or with
 * one that isn't a date, today.
 */
export function timetableDateFromQuery(
    value: unknown,
    today: Temporal.PlainDate,
    range?: TimetableRange | null,
): Temporal.PlainDate {
    const date = parseDate(value);

    return date ? clampDate(date, range?.from, range?.to) : today;
}

/** The `?date=` of a timetable page showing the date: none for today. */
export function timetableDateQuery(date: Temporal.PlainDate, today: Temporal.PlainDate): { date?: string } {
    return date.equals(today) ? {} : { date: date.toString() };
}

function parseDate(value: unknown): Temporal.PlainDate | undefined {
    if (typeof value !== "string" || !/^\d{4}-\d{2}-\d{2}$/.test(value)) {
        return undefined;
    }

    try {
        // without "reject" Temporal turns 30 February into the 28th
        return Temporal.PlainDate.from(value, { overflow: "reject" });
    } catch {
        return undefined;
    }
}
