import { Stop } from "../model";
import { ActionTree, MutationTree } from "vuex";
import urls from "../urls";

export interface RootState {
    stops: Stop[],
}

export interface SavedState {
    version: 1,
    stops: string[],
}

export const state: RootState = {
    stops: []
};

export const mutations: MutationTree<RootState> = {
    updateStops: (state, stops) => state.stops = stops,
};

export const actions: ActionTree<RootState, undefined> = {
    async load({ commit }, { stops }: SavedState) {
        if (stops.length > 0) {
            const response = await fetch(urls.prepare(urls.stops.all, { id: stops }));

            if (response.ok) {
                commit('updateStops', await response.json());
            }
        }
    },
    save: async ({ state }): Promise<SavedState> => ({
        version: 1,
        stops: state.stops.map(stop => stop.id)
    })
};