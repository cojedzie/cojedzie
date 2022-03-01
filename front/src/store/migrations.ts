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

import { distinct } from "@/utils";
import * as uuid from "uuid";
import api from "@/api";

type Migration<TState, TResult> = {
    name: string,
    key: string,
    skip?: (state: TState) => boolean,
    up: (state: TState) => Promise<TResult>,
}


const migrations: Migration<any, any>[] = [
    {
        name: "202001261540_full_stop_in_state",
        key: "vuex",
        skip: state =>
            !state
            || !state.favourites
            || !state.favourites.favourites
            || state.favourites.favourites.length == 0,
        up: async state => {
            const current = state.favourites.favourites;

            const ids = current
                .flatMap(favourite => favourite.state.stops)
                .filter(distinct)
            ;

            const stops = await api.get("v1_stop_list", {
                query: { id: ids },
                version: "^1.0"
            });

            const lookup = stops.data.reduce(
                (lookup, stop) => ({ ...lookup, [stop.id]: stop }),
                {}
            );

            return {
                ...state,
                favourites: {
                    ...state.favourites,
                    favourites: state.favourites.favourites.map(favourite => ({
                        id: favourite.id || uuid.v4(),
                        name: favourite.name,
                        stops: favourite.stops || favourite.state.stops.map(id => lookup[id]).filter(distinct),
                    }))
                }
            }
        }
    }
];

export async function migrate(key: string) {
    const current = JSON.parse(window.localStorage.getItem('migrations')) || [];
    const state   = JSON.parse(window.localStorage.getItem(key)) || {};

    const result = await migrations
        .filter(migration => migration.key == key)
        .filter(migration => !current.includes(migration.name))
        .reduce(async (state, migration) => {
            current.push(migration.name);

            if (migration.skip && migration.skip(state)) {
                return state;
            }

            return await migration.up(state)
        }, state);

    window.localStorage.setItem('migrations', JSON.stringify(current));
    window.localStorage.setItem(key, JSON.stringify(result));
}
