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
import {
    clockTimeIn,
    dateIn,
    formatClockTime,
    formatDateInWords,
    formatDateTime,
    formatRelativeTime,
    Temporal,
    wholeMinutesBetween,
} from "@/time";

const at = (instant: string) => Temporal.Instant.from(instant);
const now = at("2026-11-10T12:00:00Z");
const later = (duration: Temporal.DurationLike) => now.add(duration);

describe("times", () => {
    it("shows an instant in the time zone given", () => {
        const instant = at("2026-11-10T13:05:00Z");

        expect(formatClockTime(instant, "Europe/Warsaw")).toBe("14:05");
        expect(formatClockTime(instant, "Europe/London")).toBe("13:05");
        expect(formatDateTime(instant, "Europe/Warsaw")).toBe("10.11.2026, 14:05");
        expect(formatDateInWords(dateIn(instant, "Europe/Warsaw"))).toBe("10 listopada 2026");
        expect(clockTimeIn(at("2026-11-10T23:30:59Z"), "Europe/Warsaw").toString()).toBe("00:30:00");
        expect(dateIn(at("2026-11-10T23:30:00Z"), "Europe/Warsaw").toString()).toBe("2026-11-11");
    });

    it("counts whole minutes rounded towards zero", () => {
        expect(wholeMinutesBetween(now, later({ minutes: 44, seconds: 59 }))).toBe(44);
        expect(wholeMinutesBetween(now, now.subtract({ minutes: 1, seconds: 30 }))).toBe(-1);
    });

    it("says how far a moment is as a departure board does", () => {
        expect(formatRelativeTime(later({ seconds: 59 }), now)).toBe("za chwilę");
        expect(formatRelativeTime(later({ minutes: 1 }), now)).toBe("za 1 minutę");
        expect(formatRelativeTime(later({ minutes: 44, seconds: 59 }), now)).toBe("za 44 minuty");
        expect(formatRelativeTime(later({ minutes: 59, seconds: 59 }), now)).toBe("za 59 minut");
        expect(formatRelativeTime(later({ minutes: 60 }), now)).toBe("za 1 godzinę");
        expect(formatRelativeTime(later({ hours: 2, minutes: 59 }), now)).toBe("za 2 godziny");
        expect(formatRelativeTime(now.subtract({ seconds: 30 }), now)).toBe("przed chwilą");
        expect(formatRelativeTime(now.subtract({ minutes: 5 }), now)).toBe("5 minut temu");
    });
});
