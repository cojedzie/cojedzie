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
import type { Departure, Destination, Line, Track } from "@/model";

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
 * filled in from the stop's tracks, fetched unless given; a line no track of the stop has anymore shows its id as the
 * symbol.
 */
export async function fetchTimetableDay(
    client: Client,
    { stop, line, date }: TimetableDayQuery,
    tracks?: Track[],
): Promise<Departure[]> {
    const [entries, known] = await Promise.all([
        fetchEntries(client, { stop, line, date }),
        tracks ?? fetchStopTracks(client, stop),
    ]);
    const lines = new Map(known.map(track => [track.line.id, track.line]));

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

/** The tracks running through a stop, with their kind (version 1.1). */
export async function fetchStopTracks(client: Client, stop: string): Promise<Track[]> {
    const { data } = await client.get("v1_stop_tracks", { version: "^1.1", params: { stop } });

    return data.map(({ track }) => track as Track);
}

/** A line at a stop, with the names of where it goes from there */
export type StopLine = { line: Line; directions: string[] };

/**
 * The lines of a stop's destinations, each once, in the order of their symbols ("6", "12", "N1") as the timetable
 * lists them, each with the names of the destinations it goes to from the stop.
 */
export function linesOfStop(destinations: Destination[]): StopLine[] {
    const lines = new Map<string, StopLine>();

    for (const { stop, lines: going } of destinations) {
        for (const line of going ?? []) {
            const entry = lines.get(line.id) ?? { line, directions: [] };
            if (!entry.directions.includes(stop.name)) {
                entry.directions.push(stop.name);
            }
            lines.set(line.id, entry);
        }
    }

    return [...lines.values()].sort((a, b) => a.line.symbol.localeCompare(b.line.symbol, undefined, { numeric: true }));
}

/** The tracks of a line, in one request: the API's largest page, more than any line has. */
const LINE_TRACKS_LIMIT = 100;

export type LineWithTracks = {
    line: Line;
    /** The tracks riders can take, each with its stops in order */
    tracks: Track[];
};

/** Fetches a line and its tracks with their stops, or `null` when the provider has no such line. */
export async function fetchLine(client: Client, line: string): Promise<LineWithTracks | null> {
    try {
        const [details, tracks] = await Promise.all([
            client.get("v1_line_details", { version: "^1.0", params: { line } }),
            // `kind` is there from version 1.1; non-passenger tracks never are (ADR 0031)
            client.get("v1_track_list", {
                version: "^1.1",
                query: { line, embed: "stops", limit: LINE_TRACKS_LIMIT },
            }),
        ]);

        return { line: details.data as Line, tracks: tracks.data as Track[] };
    } catch (error) {
        if (isNotFound(error)) {
            return null;
        }

        throw error;
    }
}

function isNotFound(error: unknown): boolean {
    return (error as { response?: { status?: number } } | null)?.response?.status === 404;
}

function unknownLine(id: string): Line {
    return { id, symbol: id, type: "unknown", night: false, fast: false, $type: "vnd.cojedzie.line" };
}
