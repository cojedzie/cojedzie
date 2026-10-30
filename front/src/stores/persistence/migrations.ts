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

import { captureException } from "@sentry/vue";
import * as uuid from "uuid";
import api from "@/api";
import { distinct } from "@/utils";
import type { UserStateStorage } from "./storage";

export interface Migration {
    name: string;
    up(storage: UserStateStorage): Promise<void>;
}

export const migrations: Migration[] = [
    {
        name: "202001261540_full_stop_in_state",
        up: async storage => {
            const state = await storage.load<any>("vuex");

            if (!state?.favourites?.favourites?.length) {
                return;
            }

            const ids = state.favourites.favourites.flatMap(favourite => favourite.state.stops).filter(distinct);
            const stops = await api.get("v1_stop_list", {
                // migrations run before the client context is bound, and ZTM Gdańsk was the only provider then
                params: { provider: "trojmiasto" },
                query: { id: ids },
                version: "^1.0",
                // the app waits for migrations, don't hold it up retrying
                maxRetries: 1,
            });

            if (!stops) {
                throw new Error("Stops of the saved favourites could not be fetched");
            }

            const lookup = stops.data.reduce((lookup, stop) => ({ ...lookup, [stop.id]: stop }), {});

            await storage.save("vuex", {
                ...state,
                favourites: {
                    ...state.favourites,
                    favourites: state.favourites.favourites.map(favourite => ({
                        id: favourite.id || uuid.v4(),
                        name: favourite.name,
                        stops: favourite.stops || favourite.state.stops.map(id => lookup[id]).filter(distinct),
                    })),
                },
            });
        },
    },
];

/**
 * Runs the migrations that were not applied yet. A migration is recorded once it succeeds; a failing one stops
 * the run, is reported and gets retried next time.
 */
export async function migrate(storage: UserStateStorage, list: Migration[] = migrations): Promise<void> {
    const applied = (await storage.load<string[]>("migrations")) ?? [];

    for (const migration of list) {
        if (applied.includes(migration.name)) {
            continue;
        }

        try {
            await migration.up(storage);
        } catch (error) {
            console.error(`Migration ${migration.name} failed`, error);
            captureException(error);
            return;
        }

        applied.push(migration.name);
        await storage.save("migrations", applied);
    }
}
