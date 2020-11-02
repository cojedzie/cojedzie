import { Stop } from "@/model";
import { ActionTree, MutationTree } from "vuex";
import urls from "../urls";
import { ensureArray } from "@/utils";

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
        const response = await fetch(urls.prepare(urls.providers.get, { provider }));

        if (response.ok) {
            commit('setProvider', await response.json());
        }
    },
    async load({ commit }, { stops }: SavedState) {
        if (stops.length > 0) {
            const response = await fetch(urls.prepare(urls.stops.all, { id: stops }));

            if (response.ok) {
                commit('replace', await response.json());
            }
        }
    },
    save: async ({ state }): Promise<SavedState> => ({
        version: 1,
        stops: state.stops.map(stop => stop.id)
    })
};
