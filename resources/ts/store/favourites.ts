import { RootState } from "./root";
import { Module } from "vuex";
import { Stop } from "../model";

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
            state.favourites.push(favourite);
        },
        remove(state, favourite: Favourite) {
            state.favourites = state.favourites.filter(f => f != favourite);
        }
    }
};

export default favourites;
