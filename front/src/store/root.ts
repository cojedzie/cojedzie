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
import { ActionTree, MutationTree } from "vuex";
import { ensureArray } from "@/utils";
import api from "@/api";

export interface RootState {
    stops: Stop[],
    provider: any,
}

export interface SavedState {
    version: 1,
    stops: string[],
}

export interface LoadProviderActionPayload {
    provider: string;
}

export const state: RootState = {
    stops: [],
    provider: null,
};

export const mutations: MutationTree<RootState> = {
    add:     (state, stops) => state.stops = [...state.stops, ...ensureArray(stops)],
    replace: (state, stops) => state.stops = stops,
    remove:  (state, stop) => state.stops = state.stops.filter(s => s != stop),
    clear:   (state) => state.stops = [],
    setProvider: (state, provider) => state.provider = provider,
};

export const actions: ActionTree<RootState, undefined> = {
    async loadProvider({ commit }, { provider }) {
        const response = await api.get('v1_provider_details', {
            params: { provider },
            version: '^1.0',
        });
        commit('setProvider', response.data);
    },
    async load({ commit }, { stops }: SavedState) {
        if (stops.length > 0) {
            const response = await api.get("v1_stop_list", {
                query: { id: stops },
                version: "^1.0"
            });

            commit('replace', response.data);
        }
    },
    save: async ({ state }): Promise<SavedState> => ({
        version: 1,
        stops: state.stops.map(stop => stop.id)
    })
};
