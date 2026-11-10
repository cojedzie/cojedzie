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

import type { PredefinedIcon } from "@/icons";

export interface NavigationEntry {
    id: string;
    label: string;
    icon: PredefinedIcon;
    /** name of the route, it takes the provider as its only param */
    route: string;
    /** whether the entry is a tab of the mobile bottom bar, the others go to "Więcej" */
    bottomBar: boolean;
}

export const navigation: NavigationEntry[] = [
    { id: "departures", label: "Odjazdy", icon: "timetable", route: "departures", bottomBar: true },
    { id: "timetable", label: "Rozkład", icon: "calendar", route: "timetable", bottomBar: true },
];
