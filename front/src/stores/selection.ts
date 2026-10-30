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
import type { Provider, Stop } from "@/model";
import api from "@/api";
import { storages } from "@/stores/persistence/storages";
import { ensureArray, type Jsonified } from "@/utils";

export const useSelectionStore = defineStore(
    "selection",
    () => {
        const stops = ref<Stop[]>([]);
        const provider = ref<Jsonified<Provider> | null>(null);

        function add(added: Stop | Stop[]) {
            stops.value = [...stops.value, ...ensureArray(added)];
        }

        function replace(replacement: Stop[]) {
            stops.value = replacement;
        }

        function remove(stop: Stop) {
            stops.value = stops.value.filter(current => current != stop);
        }

        function clear() {
            stops.value = [];
        }

        async function loadProvider(id: string) {
            const response = await api.get("v1_provider_details", {
                params: { provider: id },
                version: "^1.0",
            });

            provider.value = response.data;
        }

        return { stops, provider, add, replace, remove, clear, loadProvider };
    },
    {
        persist: {
            key: "selection",
            storage: storages.session,
            pick: state => ({ stops: state.stops }),
        },
    },
);
