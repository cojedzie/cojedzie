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

/*
 * Based on vue-removed-hook-mixin v0.1.0
 * (c) 2019 James Diacono
 * @license MIT
 */

import { onBeforeUnmount, onUnmounted, Ref } from "vue";

/**
 * Calls the callback once the component is unmounted and its element is really gone from the document. When the
 * element is kept for a while (for example by a leave transition) the callback waits until it is detached.
 */
export function useRemoved(element: Ref<HTMLElement | undefined>, callback: () => void): void {
    // template refs are already cleared when unmounted hook runs
    let target: HTMLElement | undefined;

    onBeforeUnmount(() => {
        target = element.value;
    });

    onUnmounted(() => {
        // element was immediately detached from DOM (no transition)
        if (!target || !document.body.contains(target)) {
            callback();
            return;
        }

        const observer = new MutationObserver((mutations, observer) => {
            for (const { removedNodes } of mutations) {
                for (const node of Array.from(removedNodes)) {
                    if (node.contains(target)) {
                        observer.disconnect();
                        callback();
                        return;
                    }
                }
            }
        });

        observer.observe(document.body, { childList: true, subtree: true });
    });
}
