import { RootState, SavedState } from "./root";
import { Module, Plugin, Store } from "vuex";
import * as utils from "../utils";
import { Stop } from "../model";

export interface Favourite {
    id: string;
    name:  string;
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

export const localStorageSaver = (path: string, key: string): Plugin<any> => (store: Store<any>) => {
    utils.set(store.state, path, JSON.parse(window.localStorage.getItem(key) || '[]'));

    store.subscribe((mutation, state) => {
        window.localStorage.setItem(key, JSON.stringify(utils.get(state, path)));
    })
};

export default favourites;
