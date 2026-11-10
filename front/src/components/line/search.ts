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
import type { Line } from "@/model";

/** Searches lines whose symbol starts with the given one, ignoring case, in the API's display order. */
export async function searchLines(client: ApiClient<Endpoints, "provider">, symbol: string): Promise<Line[]> {
    const { data } = await client.get("v1_line_list", { query: { symbol }, version: "^1.0" });

    return data as Line[];
}
