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
import type { Stop } from "@/model";
import { storages } from "@/stores/persistence/storages";
import { except } from "@/utils/object";

export interface Favourite {
    id: string;
    name: string;
    stops: Stop[];
}

export const useFavouritesStore = defineStore(
    "favourites",
    () => {
        const favourites = ref<Favourite[]>([]);

        function add(favourite: Favourite) {
            const existing = favourites.value.find(current => current.name === favourite.name);

            if (existing) {
                Object.assign(existing, except(favourite, ["id"]));
            } else {
                favourites.value.push(favourite);
            }
        }

        function remove(favourite: Favourite) {
            favourites.value = favourites.value.filter(current => current.id !== favourite.id);
        }

        return { favourites, add, remove };
    },
    {
        persist: {
            key: "favourites",
            storage: storages.local,
            pick: state => ({ favourites: state.favourites }),
        },
    },
);
