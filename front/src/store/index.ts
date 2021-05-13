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

import Vuex, { ModuleTree, Plugin } from 'vuex';

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
import { ApiClient } from "@/api/client";
import { Endpoints } from "@/api";
import { supply } from "@/utils";
import { LoadBalancedClient } from "@/api/client/balanced";
import loadbalancer from "@/api/loadbalancer";

declare module 'vuex/types' {
    interface Store<S> {
        $api: ApiClient<Endpoints, "provider">
    }
}

export type State = {
    messages: MessagesState;
    departures: DeparturesState;
    favourites: FavouritesState;
    "departures-settings": DeparturesSettingsState;
    "messages-settings": MessagesSettingsState;
    history: HistoryState;
    network: NetworkState;
} & RootState;

const localStoragePersist = typeof window !== "undefined" && new VuexPersistence<State>({
    modules: ['favourites', 'departures-settings', 'messages-settings', 'history'],
});

const sessionStoragePersist = typeof window !== "undefined" && new VuexPersistence<State>({
    reducer: state => ({ stops: state.stops }),
    storage: window.sessionStorage
});

export type StoreOptions = {
    plugins?: Plugin<RootState>[],
    state?: RootState,
    modules?: ModuleTree<RootState>,
}

export function createStore(options?: StoreOptions) {
    const store = new Vuex.Store<RootState>({
        state: supply(options?.state || state),
        mutations,
        actions,
        modules: {
            messages,
            departures,
            favourites,
            'departures-settings': departureSettings,
            'messages-settings': messagesSettings,
            history,
            network,
            ...(options?.modules || {})
        },
        plugins: options?.plugins || [],
    })

    store.$api = new LoadBalancedClient(loadbalancer, () => ({ provider: store.state.provider?.id }));

    return store;
}

export const store = createStore({
    plugins: typeof window !== "undefined" ? [
        localStoragePersist.plugin,
        sessionStoragePersist.plugin,
    ] : []
});

export default store;

export const Favourites = namespace('favourites');
export const DeparturesSettings = namespace('departures-settings');
export const MessagesSettings = namespace('messages-settings');
export const Departures = namespace('departures');
export const Messages = namespace('messages');
export const History = namespace('history');
