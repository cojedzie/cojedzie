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
import type { Line, Track } from "@/model";
import { type LineTrack, mainTracksOfLines } from "@/timetable";

type Client = ApiClient<Endpoints, "provider">;

/** The lines whose tracks are listed, the first ones found; a short query matches more than a search can show. */
const MAX_LINES = 10;
/** The tracks of the lines in one request: the API's largest page, more than ten lines have. */
const TRACKS_LIMIT = 100;

/**
 * Searches lines whose symbol starts with the given one, ignoring case, and lists their main tracks: the lines in the
 * API's display order, a line without a main track alone.
 */
export async function searchLineTracks(client: Client, symbol: string): Promise<LineTrack[]> {
    const { data } = await client.get("v1_line_list", { query: { symbol }, version: "^1.0" });
    const lines = (data as Line[]).slice(0, MAX_LINES);

    if (lines.length === 0) {
        return [];
    }

    // `kind` is there from version 1.1
    const tracks = await client.get("v1_track_list", {
        version: "^1.1",
        query: { line: lines.map(line => line.id).join(","), limit: TRACKS_LIMIT },
    });

    return mainTracksOfLines(lines, tracks.data as Track[]);
}
