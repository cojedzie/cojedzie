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

import { formatDateInWords } from "./dates";
import { LOCALE } from "./locale";
import { Temporal } from "./temporal";

// Moments are Temporal.Instant; a time zone is applied only here, when one is shown (ADR 0033).

/** "09:05" */
export function formatClockTime(instant: Temporal.Instant, timeZone: string): string {
    return instant.toZonedDateTimeISO(timeZone).toLocaleString(LOCALE, { hour: "2-digit", minute: "2-digit" });
}

/** "09:05", a clock time of a day */
export function formatTime(time: Temporal.PlainTime): string {
    return time.toLocaleString(LOCALE, { hour: "2-digit", minute: "2-digit" });
}

/** "10.11.2026, 14:05" */
export function formatDateTime(instant: Temporal.Instant, timeZone: string): string {
    return instant.toZonedDateTimeISO(timeZone).toLocaleString(LOCALE, {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
}

/** The calendar day an instant falls on in the time zone. */
export function dateIn(instant: Temporal.Instant, timeZone: string): Temporal.PlainDate {
    return instant.toZonedDateTimeISO(timeZone).toPlainDate();
}

/** The wall clock time of an instant in the time zone, to the minute. */
export function clockTimeIn(instant: Temporal.Instant, timeZone: string): Temporal.PlainTime {
    return instant.toZonedDateTimeISO(timeZone).toPlainTime().round({ smallestUnit: "minute", roundingMode: "trunc" });
}

/** Whole minutes from `from` to `to`, rounded towards zero: 44 minutes 59 seconds are 44. */
export function wholeMinutesBetween(from: Temporal.Instant, to: Temporal.Instant): number {
    return Math.trunc(from.until(to).total("minutes"));
}

const relative = new Intl.RelativeTimeFormat(LOCALE, { numeric: "always" });
const relativeDays = new Intl.RelativeTimeFormat(LOCALE, { numeric: "auto" });

/**
 * How far `target` is from `now`, as a departure board says it: "za chwilę" within a minute, whole minutes rounded
 * down within an hour ("za 44 minuty"), whole hours beyond ("za 2 godziny"); "temu" for the past.
 */
export function formatRelativeTime(target: Temporal.Instant, now: Temporal.Instant): string {
    const seconds = now.until(target).total("seconds");

    if (Math.abs(seconds) < 60) {
        return seconds >= 0 ? "za chwilę" : "przed chwilą";
    }

    const minutes = Math.trunc(seconds / 60);

    return Math.abs(minutes) < 60
        ? relative.format(minutes, "minute")
        : relative.format(Math.trunc(minutes / 60), "hour");
}

/**
 * When a moment is, as a day and a time: "dzisiaj o 14:05", "jutro o 9:00", "wczoraj o 22:10" within a day of `now`
 * (the day words come from Intl), the date in words beyond ("10 listopada 2026"). Days are taken in the time zone the
 * time is shown in, so the two agree.
 */
export function formatDayAndTime(instant: Temporal.Instant, now: Temporal.Instant, timeZone: string): string {
    const day = dateIn(instant, timeZone);
    const days = dateIn(now, timeZone).until(day, { largestUnit: "days" }).days;

    if (Math.abs(days) > 1) {
        return formatDateInWords(day);
    }

    // Intl has no pattern joining a day word and a time ("o" in Polish, "at" in English): translate it with the rest
    // of the copy (#180)
    return `${relativeDays.format(days, "day")} o ${formatClockTime(instant, timeZone)}`;
}
