import Vuex from 'vuex';

import messages, { MessagesState } from './messages';
import departures, { DeparturesState } from './departures'
import favourites, { FavouritesState, localStorageSaver } from './favourites'

import { actions, mutations, RootState, state } from "./root";
import VuexPersistence from "vuex-persist";
import { namespace } from "vuex-class";
import departureSettings from "./settings/departures";

export type State = {
    messages: MessagesState;
    departures: DeparturesState;
    favourites: FavouritesState;
} & RootState;

const localStoragePersist = new VuexPersistence<State>({
    modules: ['favourites', 'departures-settings'],
});

const sessionStoragePersist = new VuexPersistence<State>({
    reducer: state => ({ stops: state.stops }),
    storage: window.sessionStorage
});

const store = new Vuex.Store({
    state, mutations, actions,
    modules: {
        messages,
        departures,
        favourites,
        'departures-settings': departureSettings
    },
    plugins: [
        // todo: remove after some time
        localStorageSaver('favourites.favourites', 'favourites'),
        localStoragePersist.plugin,
        sessionStoragePersist.plugin,
    ]
});

export default store;

export const Favourites = namespace('favourites');
export const DeparturesSettings = namespace('departures-settings');
export const Departures = namespace('departures');
