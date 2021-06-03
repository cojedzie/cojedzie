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
import network, { NetworkState } from "@/store/network";

export type State = {
    messages: MessagesState;
    departures: DeparturesState;
    favourites: FavouritesState;
    "departures-settings": DeparturesSettingsState;
    "messages-settings": MessagesSettingsState;
    history: HistoryState;
    network: NetworkState;
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
        network,
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
