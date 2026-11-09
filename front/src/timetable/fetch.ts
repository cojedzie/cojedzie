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

import type { ApiClient } from "@/api/client";
import type { Endpoints } from "@/api/endpoints";
import type { UrlParams } from "@/api/utils";
import { Temporal } from "@/time";
import type { Departure, Line } from "@/model";

/**
 * Which entries make up a day (ADR 0029). Provisional: the views are to be tried with it, so nothing may depend on
 * its value.
 */
export const TIMETABLE_DAY_MODE: "calendar" | "service" | "extended" = "extended";

/** The largest page the API serves; a busy stop's day takes more than one. */
export const TIMETABLE_PAGE_SIZE = 1000;

export type TimetableDayQuery = {
    stop: string;
    /** Only this line's entries */
    line?: string;
    /** The day in the provider's time zone */
    date: Temporal.PlainDate;
};

type Client = ApiClient<Endpoints, "provider">;

/**
 * Fetches every timetable entry of a stop's day. Entries carry only a reference of their line, so the lines are
 * filled in from the stop's tracks; a line no track of the stop has anymore shows its id as the symbol.
 */
export async function fetchTimetableDay(client: Client, { stop, line, date }: TimetableDayQuery): Promise<Departure[]> {
    const [entries, lines] = await Promise.all([fetchEntries(client, { stop, line, date }), fetchLines(client, stop)]);

    return entries.map((entry): Departure => ({
        ...entry,
        line: lines.get(entry.line.id) ?? unknownLine(entry.line.id),
        scheduled: Temporal.Instant.from(entry.scheduled),
        estimated: entry.estimated ? Temporal.Instant.from(entry.estimated) : null,
        serviceDay: entry.serviceDay ? Temporal.PlainDate.from(entry.serviceDay) : null,
    }));
}

async function fetchEntries(client: Client, { stop, line, date }: TimetableDayQuery) {
    const entries = [];

    for (let offset = 0; ; offset += TIMETABLE_PAGE_SIZE) {
        const query: UrlParams = { date: date.toString(), day: TIMETABLE_DAY_MODE };
        if (line) {
            query.line = line;
        }

        const { data } = await client.get("v1_stop_timetable", {
            version: "^1.0",
            params: { stop },
            query: { ...query, limit: TIMETABLE_PAGE_SIZE, offset },
        });

        entries.push(...data);

        if (data.length < TIMETABLE_PAGE_SIZE) {
            return entries;
        }
    }
}

async function fetchLines(client: Client, stop: string): Promise<Map<string, Line>> {
    const { data } = await client.get("v1_stop_tracks", { version: "^1.0", params: { stop } });

    return new Map(data.map(({ track }) => [track.line.id, track.line as Line]));
}

function unknownLine(id: string): Line {
    return { id, symbol: id, type: "unknown", night: false, fast: false, $type: "vnd.cojedzie.line" };
}
