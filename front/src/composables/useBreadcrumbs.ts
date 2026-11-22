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

import { computed, type MaybeRefOrGetter, onScopeDispose, type Ref, shallowRef, toValue, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { buildTrail, type Crumb, type TrailData } from "@/breadcrumbs";

/** How long a crumb keeps showing what it showed before while its new line or stop loads */
export const BREADCRUMB_HOLD_MS = 1000;

/**
 * The breadcrumbs of the current route, named from what the pages loaded (see `buildTrail`).
 *
 * Opening another stop or line would flash the crumb's generic word ("Przystanek") between two names, so a crumb whose
 * new content is pending keeps showing what it showed for the same route, until the content arrives or for at most
 * `BREADCRUMB_HOLD_MS`, after which a load that never came shows the generic word after all.
 */
export function useBreadcrumbs(data: MaybeRefOrGetter<TrailData>): Readonly<Ref<Crumb[]>> {
    const route = useRoute();
    const router = useRouter();

    const built = computed(() => buildTrail(route, router, toValue(data)));
    const shown = shallowRef<Crumb[]>([]);

    let timer: ReturnType<typeof setTimeout> | undefined;
    const stopHolding = () => {
        clearTimeout(timer);
        timer = undefined;
    };

    watch(
        built,
        trail => {
            const previous = shown.value;
            let holding = false;

            shown.value = trail.map((crumb, index) => {
                const before = previous[index];
                if (!crumb.pending || !before || before.pending || before.to.name !== crumb.to.name) {
                    return crumb;
                }

                holding = true;
                return { ...crumb, label: before.label, content: before.content, pending: false };
            });

            if (!holding) {
                stopHolding();
            } else if (!timer) {
                timer = setTimeout(() => {
                    timer = undefined;
                    shown.value = built.value;
                }, BREADCRUMB_HOLD_MS);
            }
        },
        { immediate: true },
    );

    onScopeDispose(stopHolding);

    return shown;
}
