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

import Vuex, { ModuleTree, Plugin } from "vuex";
import { actions, mutations, RootState, state } from "@/store/root";
import { supply } from "@/utils";
import messages, { MessagesState } from "@/store/modules/messages";
import departures, { DeparturesState } from "@/store/modules/departures";
import favourites, { FavouritesState } from "@/store/modules/favourites";
import departureSettings, { DeparturesSettingsState } from "@/store/modules/settings/departures";
import messagesSettings, { MessagesSettingsState } from "@/store/modules/settings/messages";
import history, { HistoryState } from "@/store/modules/history";
import network, { NetworkState } from "@/store/modules/network";
import { LoadBalancedClient } from "@/api/client/balanced";
import { LoadBalancerImplementation } from "@/api/loadbalancer";
import { Endpoints, endpoints } from "@/api/endpoints";
import { ApiClient } from "@/api/client";

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

    store.$api = new LoadBalancedClient(
        new LoadBalancerImplementation(endpoints, store),
        store,
        () => ({ provider: store.state.provider?.id })
    );

    return store;
}


