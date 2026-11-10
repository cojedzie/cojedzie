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

import type { Stop, Track } from "@/model";

/** The order riders pick a line's tracks in: the main ones first, then the side ones, each kind in the API's order. */
export function orderTracks(tracks: Track[]): Track[] {
    // a track without a kind (from an API older than 1.1) counts as a side one, like any a provider can't tell (ADR 0031)
    const main = tracks.filter(track => track.kind === "main");

    return [...main, ...tracks.filter(track => track.kind !== "main")];
}

type TrackChoice = {
    /** The page's `?track=` */
    query: unknown;
    /** The open stop, if any */
    stop?: string;
};

/**
 * The track a line page shows: the one its `?track=` names, when the line has it; otherwise the first one through the
 * open stop; otherwise the first one, main before side. An unknown `?track=` falls back silently, like an invalid
 * `?date=`.
 */
export function chooseTrack(tracks: Track[], { query, stop }: TrackChoice): Track | undefined {
    const asked = typeof query === "string" ? tracks.find(track => track.id === query) : undefined;
    const throughStop = stop !== undefined ? trackThroughStop(tracks, stop) : undefined;

    return asked ?? throughStop ?? orderTracks(tracks)[0];
}

/** The first track through the stop, main before side, judged by the tracks' stops. */
function trackThroughStop(tracks: Track[], stop: string): Track | undefined {
    return orderTracks(tracks).find(track => track.stops?.some(({ id }) => id === stop));
}

/** Where the track goes: its destination, or else its last stop. */
export function trackDestination(track: Track): Stop | undefined {
    return track.destination ?? track.stops?.at(-1);
}

/**
 * The track's label among the line's tracks, "→ Jelitkowo"; tracks sharing one get their first stop too,
 * "Łostowice → Jelitkowo".
 */
export function trackLabel(track: Track, tracks: Track[]): string {
    const label = destinationLabel(track);
    const shared = tracks.some(other => other.id !== track.id && destinationLabel(other) === label);

    return shared ? `${track.stops?.[0]?.name ?? ""} ${label}` : label;
}

function destinationLabel(track: Track): string {
    return `→ ${trackDestination(track)?.name ?? ""}`;
}
