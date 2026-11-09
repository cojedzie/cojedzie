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

import { Temporal } from "./temporal";

// Moments are Temporal.Instant; a time zone is applied only here, when one is shown (ADR 0033).

/** "09:05" */
export function formatClockTime(instant: Temporal.Instant, timeZone: string): string {
    return instant.toZonedDateTimeISO(timeZone).toLocaleString("pl", { hour: "2-digit", minute: "2-digit" });
}

/** "10.11.2026, 14:05" */
export function formatDateTime(instant: Temporal.Instant, timeZone: string): string {
    return instant.toZonedDateTimeISO(timeZone).toLocaleString("pl", {
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

const relative = new Intl.RelativeTimeFormat("pl", { numeric: "always" });

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
