/*
 * Copyright (C) 2021 Kacper Donat
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

import { Stop } from "@/model";
import { except, supply } from "@/utils";
import { NamespacedVuexModule, VuexMutationHandler } from "vuex";

export interface Favourite {
    id: string;
    name: string;
    stops: Stop[];
}

export interface FavouritesState {
    favourites: Favourite[];
}

export type FavouritesMutationTree = {
    add: VuexMutationHandler<FavouritesState, Favourite>,
    remove: VuexMutationHandler<FavouritesState, Favourite>,
}

export type FavouritesModule = NamespacedVuexModule<FavouritesState, FavouritesMutationTree>

const favourites: FavouritesModule = {
    namespaced: true,
    state: supply({
        favourites: []
    }),
    mutations: {
        add(state, favourite: Favourite) {
            const existing = state.favourites.find((current: Favourite) => current.name === favourite.name);

            if (!existing) {
                state.favourites.push(favourite);
            }

            Object.assign(existing, except(favourite, ["id"]));
        },
        remove(state, favourite: Favourite) {
            state.favourites = state.favourites.filter(f => f != favourite);
        }
    }
};

export default favourites;
