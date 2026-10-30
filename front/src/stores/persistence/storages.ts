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

import { BrowserStorage } from "./browser";
import type { UserStateStorage } from "./storage";

function lazy(create: () => UserStateStorage) {
    let target: UserStateStorage | undefined;

    return {
        storage: {
            load: (key: string) => (target ??= create()).load(key),
            save: (key: string, value: unknown) => (target ??= create()).save(key, value),
            remove: (key: string) => (target ??= create()).remove(key),
        } as UserStateStorage,
        replace(storage: UserStateStorage | undefined) {
            target = storage;
        },
    };
}

const local = lazy(() => new BrowserStorage(window.localStorage));
const session = lazy(() => new BrowserStorage(window.sessionStorage));

/**
 * The storages stores persist to. They touch `window` only on first use, so stores can be defined anywhere.
 */
export const storages = {
    local: local.storage,
    session: session.storage,
};

/** Swaps the backing storages, tests use it to avoid the browser. Without arguments it restores the defaults. */
export function replaceStorages(replacements: { local?: UserStateStorage; session?: UserStateStorage } = {}) {
    local.replace(replacements.local);
    session.replace(replacements.session);
}
