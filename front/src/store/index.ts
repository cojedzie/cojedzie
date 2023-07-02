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

import { namespace } from "vuex-class";
import VuexPersistence from "vuex-persist";
import { createStore, State } from "@/store/initializer";
import { createHttpClient } from "@/api/client/http";

export const Favourites = namespace("favourites");
export const DeparturesSettings = namespace("departures-settings");
export const MessagesSettings = namespace("messages-settings");
export const Departures = namespace("departures");
export const Messages = namespace("messages");
export const History = namespace("history");

const localStoragePersist =
    typeof window !== "undefined" &&
    new VuexPersistence<State>({
        modules: ["favourites", "departures-settings", "messages-settings", "history"],
    });

const sessionStoragePersist =
    typeof window !== "undefined" &&
    new VuexPersistence<State>({
        reducer: state => ({ stops: state.stops }),
        storage: window.sessionStorage,
    });

export const store = createStore({
    plugins: typeof window !== "undefined" ? [localStoragePersist.plugin, sessionStoragePersist.plugin] : [],
    apiClientOptions: {
        http: createHttpClient({
            baseURL: window.CoJedzie?.api?.base || `${window.location.protocol}//${window.location.host}`,
        }),
    },
});

export default store;
