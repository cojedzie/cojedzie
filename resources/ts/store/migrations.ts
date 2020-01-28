import { distinct } from "../utils";
import urls from "../urls";
import * as uuid from "uuid";

type Migration = {
    name: string,
    key: string,
    skip?: (state: any) => boolean,
    up: (state: any) => Promise<any>,
}

const migrations: Migration[] = [
    {
        name: "202001261540_full_stop_in_state",
        key: "vuex",
        skip: state => !state || !state.favourites || !state.favourites.favourites,
        up: async state => {
            const current = state.favourites.favourites;

            const ids = current
                .map(favourite => favourite.state.stops)
                .reduce((cur, acc) => [ ...cur, ...acc ])
                .filter(distinct)
            ;

            const stops  = await (await fetch(urls.prepare(urls.stops.all, { id: ids }))).json();
            const lookup = stops.reduce((lookup, stop) => ({ ...lookup, [stop.id]: stop }), {});

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
        .filter(migration => !migration.skip || !migration.skip(state))
        .reduce(async (state, migration) => {
            current.push(migration.name);
            return await migration.up(state)
        }, state);

    window.localStorage.setItem('migrations', JSON.stringify(current));
    window.localStorage.setItem(key, JSON.stringify(result));
}
