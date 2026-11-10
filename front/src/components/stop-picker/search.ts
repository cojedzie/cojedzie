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
import type { StopWithDestinations } from "@/model";

/** Stops of one stop group, the group's name being only a heading: a group can't be chosen as a whole. */
export type FoundStopGroup = {
    name: string;
    stops: StopWithDestinations[];
};

/**
 * Searches stop groups by name, each stop with its destinations, as the departures page's stop picker and the
 * timetable search show them.
 */
export async function searchStopGroups(
    client: ApiClient<Endpoints, "provider">,
    name: string,
): Promise<FoundStopGroup[]> {
    const { data } = await client.get("v1_stop_group_list", {
        query: { name, embed: "destinations" },
        version: "^1.0",
    });

    return data as FoundStopGroup[];
}
