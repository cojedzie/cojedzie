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

import { ref } from "vue";
import moment, { type Moment } from "moment";
import type { FetchingState } from "@/utils";

/**
 * State shared by the stores that fetch a list from the API: whether a request runs, when the last one succeeded and
 * why the last one failed.
 */
export function useFetching() {
    const state = ref<FetchingState>("not-initialized");
    const lastUpdate = ref<Moment>(moment());
    const error = ref<string>("");

    async function fetching(request: () => Promise<void>) {
        state.value = "fetching";

        try {
            await request();

            lastUpdate.value = moment();
            state.value = "ready";
        } catch (response) {
            error.value = JSON.stringify(response);
            state.value = "error";
        }
    }

    return { state, lastUpdate, error, fetching };
}
