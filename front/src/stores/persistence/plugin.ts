/*
 * Copyright (C) 2026 Kacper Donat
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

import type { PiniaPlugin } from "pinia";
import type { UserStateStorage } from "./storage";

export interface PersistOptions<S> {
    key: string;
    storage: UserStateStorage;
    /** Selects what gets saved. Defaults to the whole state. */
    pick?: (state: S) => unknown;
    /** Applies saved data to the state. Defaults to `$patch(saved)`. */
    restore?: (saved: unknown, state: S) => void;
}

declare module "pinia" {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    export interface DefineStoreOptionsBase<S, Store> {
        persist?: PersistOptions<S>;
    }

    export interface PiniaCustomProperties {
        $hydrated: Promise<void>;
    }
}

interface PendingSave {
    timer: ReturnType<typeof setTimeout>;
    run: () => Promise<void>;
}

const pending = new Set<PendingSave>();
let listening = false;

export function flushPersistence(): Promise<void> {
    const saves = [...pending];
    pending.clear();

    return Promise.all(
        saves.map(save => {
            clearTimeout(save.timer);
            return save.run();
        }),
    ).then(() => undefined);
}

function listenForPageHide() {
    if (listening || typeof document === "undefined") {
        return;
    }

    listening = true;
    document.addEventListener("visibilitychange", () => {
        if (document.visibilityState === "hidden") {
            void flushPersistence();
        }
    });
    window.addEventListener("pagehide", () => void flushPersistence());
}

export function persistence({ debounce = 500 }: { debounce?: number } = {}): PiniaPlugin {
    return ({ store, options }) => {
        const persist = options.persist;

        if (!persist) {
            store.$hydrated = Promise.resolve();
            return;
        }

        listenForPageHide();

        const { key, storage, pick = state => state, restore } = persist;
        let current: PendingSave | undefined;

        const subscribe = () => {
            store.$subscribe(
                () => {
                    if (current) {
                        clearTimeout(current.timer);
                        pending.delete(current);
                    }

                    const save: PendingSave = {
                        timer: setTimeout(() => {
                            pending.delete(save);
                            void save.run();
                        }, debounce),
                        run: () => storage.save(key, pick(store.$state)),
                    };

                    current = save;
                    pending.add(save);
                },
                { detached: true, flush: "sync" },
            );
        };

        // A failed load leaves the store unsaved for this session, so defaults never overwrite unreadable data.
        store.$hydrated = storage
            .load<unknown>(key)
            .then(saved => {
                if (saved !== undefined) {
                    if (restore) {
                        restore(saved, store.$state);
                    } else {
                        store.$patch(saved as object);
                    }
                }

                subscribe();
            })
            .catch(error => {
                console.error(`Could not restore saved state "${key}", it won't be saved this session.`, error);
            });
    };
}
