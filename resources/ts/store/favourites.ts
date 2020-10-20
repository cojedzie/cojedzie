import { RootState } from "./root";
import { Module } from "vuex";
import { Stop } from "../model";
import { except } from "../utils";

export interface Favourite {
    id: string;
    name: string;
    stops: Stop[];
}

export interface FavouritesState {
    favourites: Favourite[];
}

const favourites: Module<FavouritesState, RootState> = {
    namespaced: true,
    state: {
        favourites: []
    },
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
