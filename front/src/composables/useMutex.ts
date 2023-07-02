/*
 * Copyright (C) 2022 Kacper Donat
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

import { computed, ComputedRef, ref, Ref } from "vue";

export interface Mutex {
    current: Ref<symbol | null>;

    use(symbol?: symbol): MutexInstance;
}

export interface MutexInstance {
    active: ComputedRef<boolean>;

    acquire(): void;

    release(): void;

    toggle(): void;
}

export function createMutex(): Mutex {
    const current = ref<symbol | null>(null);

    return {
        current,
        use(symbol = Symbol()): MutexInstance {
            const active = computed(() => current.value === symbol);

            function acquire() {
                current.value = symbol;
            }

            function release() {
                if (active.value) {
                    current.value = null;
                }
            }

            function toggle() {
                if (!active.value) {
                    acquire();
                } else {
                    release();
                }
            }

            return {
                active,
                acquire,
                release,
                toggle,
            };
        },
    };
}

export function useMutex(mutex: Mutex, symbol = Symbol()): MutexInstance {
    return mutex.use(symbol);
}
