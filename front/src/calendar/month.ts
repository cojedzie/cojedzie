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
import { isWithin } from "./dates";

/** A day cell of a month's calendar. Whatever a cell shows about its day belongs here. */
export type CalendarDay = {
    date: Temporal.PlainDate;
    /** False for the days of the neighbouring months that fill the first and last week */
    inMonth: boolean;
    today: boolean;
    selected: boolean;
    /** Outside the range that can be chosen */
    disabled: boolean;
};

export type CalendarMonth = {
    month: Temporal.PlainYearMonth;
    /** Always six weeks from Monday, so the calendar keeps its height from month to month */
    weeks: CalendarDay[][];
};

export type CalendarOptions = {
    selected?: Temporal.PlainDate;
    today?: Temporal.PlainDate;
    min?: Temporal.PlainDate;
    max?: Temporal.PlainDate;
};

export function buildMonth(
    month: Temporal.PlainYearMonth,
    { selected, today, min, max }: CalendarOptions = {},
): CalendarMonth {
    const first = month.toPlainDate({ day: 1 });
    const start = first.subtract({ days: first.dayOfWeek - 1 });

    const days = Array.from({ length: 42 }, (_, index): CalendarDay => {
        const date = start.add({ days: index });

        return {
            date,
            inMonth: date.toPlainYearMonth().equals(month),
            today: !!today?.equals(date),
            selected: !!selected?.equals(date),
            disabled: !isWithin(date, min, max),
        };
    });

    return { month, weeks: Array.from({ length: 6 }, (_, week) => days.slice(week * 7, week * 7 + 7)) };
}
