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

import type { UserStateStorage } from "./storage";

export class MemoryStorage implements UserStateStorage {
    private readonly values = new Map<string, string>();

    constructor(initial: Record<string, unknown> = {}) {
        for (const [key, value] of Object.entries(initial)) {
            this.values.set(key, JSON.stringify(value));
        }
    }

    async load<T>(key: string): Promise<T | undefined> {
        const raw = this.values.get(key);

        return raw === undefined ? undefined : (JSON.parse(raw) as T);
    }

    async save<T>(key: string, value: T): Promise<void> {
        this.values.set(key, JSON.stringify(value));
    }

    async remove(key: string): Promise<void> {
        this.values.delete(key);
    }
}
