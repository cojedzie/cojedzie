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

import type { Line, Stop, Track } from "@/model";

// Stories of the line page's parts: a tram line with a shortened run and a bus line, in both directions.

const line = (id: string, symbol: string, type: Line["type"]): Line => ({
    id,
    symbol,
    type,
    night: false,
    fast: false,
    $type: "vnd.cojedzie.line",
});

const stop = (id: string, name: string, variant = "01"): Stop => ({ id, name, variant, $type: "vnd.cojedzie.stop" });

const stops = {
    lostowice: stop("lostowice", "Łostowice Świętokrzyska"),
    brama: stop("brama", "Brama Wyżynna"),
    dworzec: stop("dworzec", "Dworzec Główny"),
    wrzeszcz: stop("wrzeszcz", "Zajezdnia Wrzeszcz"),
    oliwa: stop("oliwa", "Oliwa"),
    jelitkowo: stop("jelitkowo", "Jelitkowo"),
    oliwaPkp: stop("oliwa-pkp", "Oliwa PKP", "02"),
    zabianka: stop("zabianka", "Żabianka SKM"),
};

const track = (id: string, line: Line, kind: Track["kind"], route: Stop[]): Track => ({
    id,
    kind,
    description: "",
    line,
    stops: route,
    destination: route.at(-1),
});

const six = line("l6", "6", "tram");
const bus = line("l111", "111", "bus");

const { lostowice, brama, dworzec, wrzeszcz, oliwa, jelitkowo, oliwaPkp, zabianka } = stops;

export const tracks = {
    /** The tram's main tracks in both directions and a run shortened to Zajezdnia Wrzeszcz */
    tram: [
        track("t6-out", six, "main", [lostowice, brama, dworzec, wrzeszcz, oliwa, jelitkowo]),
        track("t6-in", six, "main", [jelitkowo, oliwa, wrzeszcz, dworzec, brama, lostowice]),
        track("t6-short", six, "side", [lostowice, brama, dworzec, wrzeszcz]),
    ],
    bus: [
        track("t111-out", bus, "main", [oliwaPkp, oliwa, zabianka]),
        track("t111-in", bus, "main", [zabianka, oliwa, oliwaPkp]),
    ],
};
