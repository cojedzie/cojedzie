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

import type { CrumbDeclaration, TrailData, TrailRoute } from "@/breadcrumbs";
import StopLabel from "@/components/stop/StopLabel.vue";
import LineCrumb from "@/components/timetable/LineCrumb.vue";

// the timetable's crumbs name a line or stop only once its page loaded the one its route names

const loadedStop = (route: TrailRoute, { stop }: TrailData) => (stop?.id === route.params.stop ? stop : null);
const loadedLine = (route: TrailRoute, { line }: TrailData) => (line?.id === route.params.line ? line : null);

/** A stop as riders tell it apart: its name and its code in a badge ("Brama Wyżynna 01" as text) */
export const stopCrumb: CrumbDeclaration = {
    label: (route, data) => {
        const stop = loadedStop(route, data);
        return stop ? [stop.name, stop.variant].filter(Boolean).join(" ") : "Przystanek";
    },
    content: (route, data) => {
        const stop = loadedStop(route, data);
        return stop && { component: StopLabel, props: { stop, truncate: true } };
    },
};

/** A line as the word and its symbol ("Linia [8]"), "Linia 8" as text */
export const lineCrumb: CrumbDeclaration = {
    label: (route, data) => {
        const line = loadedLine(route, data);
        return line ? `Linia ${line.symbol}` : "Linia";
    },
    content: (route, data) => {
        const line = loadedLine(route, data);
        return line && { component: LineCrumb, props: { line } };
    },
};
