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

import { LOCALE } from "./locale";
import { Temporal } from "./temporal";

/** Today's calendar date in the given time zone. */
export function todayIn(timeZone: string, now: Temporal.Instant = Temporal.Now.instant()): Temporal.PlainDate {
    return now.toZonedDateTimeISO(timeZone).toPlainDate();
}

/** The date limited to the range; either end may be open. */
export function clampDate(
    date: Temporal.PlainDate,
    min?: Temporal.PlainDate,
    max?: Temporal.PlainDate,
): Temporal.PlainDate {
    if (min && Temporal.PlainDate.compare(date, min) < 0) {
        return min;
    }

    return max && Temporal.PlainDate.compare(date, max) > 0 ? max : date;
}

/** Whether the date is within the range; either end may be open. */
export function isWithin(date: Temporal.PlainDate, min?: Temporal.PlainDate, max?: Temporal.PlainDate): boolean {
    return clampDate(date, min, max).equals(date);
}

/** "poniedziałek, 09.11.2026" */
export function formatLongDate(date: Temporal.PlainDate): string {
    return date.toLocaleString(LOCALE, { weekday: "long", day: "2-digit", month: "2-digit", year: "numeric" });
}

/** "pon. 09.11" */
export function formatShortDate(date: Temporal.PlainDate): string {
    // Intl puts a comma between the weekday and the date ("pon., 09.11"), dates of timetables go without it. A pattern
    // written by hand: translate it with the rest of the copy (#180).
    return `${date.toLocaleString(LOCALE, { weekday: "short" })} ${date.toLocaleString(LOCALE, { day: "2-digit", month: "2-digit" })}`;
}

/** "10 listopada 2026" */
export function formatDateInWords(date: Temporal.PlainDate): string {
    return date.toLocaleString(LOCALE, { dateStyle: "long" });
}

/** "listopad 2026" */
export function formatMonth(month: Temporal.PlainYearMonth): string {
    // a year-month formats only in its own calendar, which isn't the locale's, so it goes through its first day
    return month.toPlainDate({ day: 1 }).toLocaleString(LOCALE, { month: "long", year: "numeric" });
}

/** Names of the days of the week from Monday, short ("pon.") and long ("poniedziałek"). */
export function weekdayNames(): { short: string; long: string }[] {
    // 2026-11-09 is a Monday
    const monday = Temporal.PlainDate.from("2026-11-09");

    return Array.from({ length: 7 }, (_, index) => {
        const date = monday.add({ days: index });

        return {
            short: date.toLocaleString(LOCALE, { weekday: "short" }),
            long: date.toLocaleString(LOCALE, { weekday: "long" }),
        };
    });
}
