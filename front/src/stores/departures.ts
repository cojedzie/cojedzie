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

import { defineStore } from "pinia";
import { ref } from "vue";
import moment from "moment";
import type { Departure, Line } from "@/model";
import api from "@/api";
import { useFetching } from "@/stores/fetching";
import { useSelectionStore } from "@/stores/selection";
import { useDeparturesSettingsStore } from "@/stores/departures-settings";

export const useDeparturesStore = defineStore("departures", () => {
    const departures = ref<Departure[]>([]);
    const { state, lastUpdate, error, fetching } = useFetching();

    const selection = useSelectionStore();
    const settings = useDeparturesSettingsStore();

    async function update() {
        const stops = selection.stops;

        if (stops.length == 0) {
            return;
        }

        await fetching(async () => {
            const response = await api.get("v1_departure_list", {
                version: "^1.0",
                query: {
                    stop: stops.map(stop => stop.id),
                    limit: settings.displayedEntriesCount || 8,
                },
            });

            departures.value = response.data.map((departure): Departure => ({
                ...departure,
                line: departure.line as Line,
                scheduled: moment.parseZone(departure.scheduled).local(),
                estimated: departure.estimated && moment.parseZone(departure.estimated).local(),
            }));
        });
    }

    return { departures, state, lastUpdate, error, update };
});
