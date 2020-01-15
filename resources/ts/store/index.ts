import Vuex from 'vuex';

import messages, { MessagesState } from './messages';
import departures, { DeparturesState } from './departures'
import favourites, { FavouritesState, localStorageSaver } from './favourites'

import { state, mutations, actions, RootState } from "./root";
import VuexPersistence from "vuex-persist";

export type State = {
    messages: MessagesState;
    departures: DeparturesState;
    favourites: FavouritesState;
} & RootState;

const localStoragePersist = new VuexPersistence<State>({
    reducer: state => ({ favourites: state.favourites })
});

const sessionStoragePersist = new VuexPersistence<State>({
    reducer: state => ({ stops: state.stops }),
    storage: window.sessionStorage
});

const store = new Vuex.Store({
    state, mutations, actions,
    modules: { messages, departures, favourites },
    plugins: [
        // todo: remove after some time
        localStorageSaver('favourites.favourites', 'favourites'),
        localStoragePersist.plugin,
        sessionStoragePersist.plugin,
    ]
});

export default store;
