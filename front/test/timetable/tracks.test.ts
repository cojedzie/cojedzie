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
import { chooseTrack, orderTracks, trackLabel } from "@/timetable";
import type { Line, Stop, Track } from "@/model";

const six: Line = { id: "l6", symbol: "6", type: "tram", night: false, fast: false, $type: "vnd.cojedzie.line" };

const stop = (id: string, name: string): Stop => ({ id, name, $type: "vnd.cojedzie.stop" });
const lostowice = stop("a", "Łostowice");
const brama = stop("s1", "Brama Wyżynna");
const wrzeszcz = stop("b", "Wrzeszcz");
const jelitkowo = stop("c", "Jelitkowo");

const track = (id: string, kind: Track["kind"], stops: Stop[], destination?: Stop): Track => ({
    id,
    kind,
    description: "",
    line: six,
    stops,
    destination,
});

const toJelitkowo = track("t1", "main", [lostowice, brama, wrzeszcz, jelitkowo], jelitkowo);
const toLostowice = track("t2", "main", [jelitkowo, wrzeszcz, lostowice], lostowice);
const toWrzeszcz = track("t3", "side", [lostowice, brama, wrzeszcz], wrzeszcz);
const shortToJelitkowo = track("t4", "main", [wrzeszcz, jelitkowo], jelitkowo);

const ids = (tracks: Track[]) => tracks.map(track => track.id);

describe("orderTracks", () => {
    it("lists the main tracks first, then the side ones, keeping the API's order within each kind", () => {
        expect(ids(orderTracks([toWrzeszcz, toLostowice, shortToJelitkowo, toJelitkowo]))).toEqual([
            "t2",
            "t4",
            "t1",
            "t3",
        ]);
    });
});

describe("chooseTrack", () => {
    const tracks = [toWrzeszcz, toLostowice, toJelitkowo];

    it("is the first main track when the query names several tracks", () => {
        expect(chooseTrack(tracks, { query: ["t3", "t1"] })?.id).toBe("t2");
    });

    it("is the first track through the open stop, main before side, unless the query asks for one", () => {
        expect(chooseTrack(tracks, { query: undefined, stop: "s1" })?.id).toBe("t1");
        expect(chooseTrack(tracks, { query: "t3", stop: "s1" })?.id).toBe("t3");
    });

    it("is a side track through the open stop when no main one runs through it", () => {
        expect(chooseTrack([toWrzeszcz, toLostowice], { query: undefined, stop: "s1" })?.id).toBe("t3");
    });

    it("is the first track when no track runs through the open stop", () => {
        expect(chooseTrack(tracks, { query: undefined, stop: "elsewhere" })?.id).toBe("t2");
    });
});

describe("trackLabel", () => {
    it("takes the last stop when the track has no destination", () => {
        const noDestination = { ...toWrzeszcz, destination: undefined };

        expect(trackLabel(noDestination, [noDestination])).toBe("→ Wrzeszcz");
    });

    it("tells tracks to the same destination apart by their first stop", () => {
        const tracks = [toLostowice, toJelitkowo, shortToJelitkowo];

        expect(tracks.map(track => trackLabel(track, tracks))).toEqual([
            "→ Łostowice",
            "Łostowice → Jelitkowo",
            "Wrzeszcz → Jelitkowo",
        ]);
    });
});
