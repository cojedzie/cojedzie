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

import { type MaybeRefOrGetter, onScopeDispose, type Ref, shallowRef, toValue, watch } from "vue";

/**
 * A value that, once it was something, keeps it for at most `ms` while it is `null`: a page's name going from one
 * entity to the next shows the previous name while the next one loads, rather than a placeholder between the two.
 * A load that never arrives shows `null` after `ms`.
 */
export function useHeld<T>(source: MaybeRefOrGetter<T | null>, ms: number): Readonly<Ref<T | null>> {
    const shown = shallowRef<T | null>(toValue(source));
    let timer: ReturnType<typeof setTimeout> | undefined;

    const stopHolding = () => {
        clearTimeout(timer);
        timer = undefined;
    };

    watch(
        () => toValue(source),
        value => {
            if (value !== null || shown.value === null) {
                stopHolding();
                shown.value = value;
            } else if (!timer) {
                timer = setTimeout(() => {
                    timer = undefined;
                    shown.value = toValue(source);
                }, ms);
            }
        },
    );

    onScopeDispose(stopHolding);

    return shown;
}
