import Vuex from 'vuex';

import messages, { MessagesState } from './messages';
import departures, { DeparturesState } from './departures'
import favourites, { FavouritesState } from './favourites'
import history, { HistoryState } from "./history";
import departureSettings, { DeparturesSettingsState } from "./settings/departures";
import messagesSettings, { MessagesSettingsState } from "./settings/messages";

import { actions, mutations, RootState, state } from "./root";
import VuexPersistence from "vuex-persist";
import { namespace } from "vuex-class";

export type State = {
    messages: MessagesState;
    departures: DeparturesState;
    favourites: FavouritesState;
    "departures-settings": DeparturesSettingsState;
    "messages-settings": MessagesSettingsState;
    history: HistoryState;
} & RootState;

const localStoragePersist = new VuexPersistence<State>({
    modules: ['favourites', 'departures-settings', 'messages-settings', 'history'],
});

const sessionStoragePersist = new VuexPersistence<State>({
    reducer: state => ({ stops: state.stops }),
    storage: window.sessionStorage
});

const store = new Vuex.Store<RootState>({
    state, mutations, actions,
    modules: {
        messages,
        departures,
        favourites,
        'departures-settings': departureSettings,
        'messages-settings': messagesSettings,
        history,
    },
    plugins: [
        localStoragePersist.plugin,
        sessionStoragePersist.plugin,
    ]
});

export default store;

export const Favourites = namespace('favourites');
export const DeparturesSettings = namespace('departures-settings');
export const MessagesSettings = namespace('messages-settings');
export const Departures = namespace('departures');
export const Messages = namespace('messages');
export const History = namespace('history');
