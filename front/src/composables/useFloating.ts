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

import { arrow, autoPlacement, autoUpdate, computePosition, flip, offset, Placement, shift } from "@floating-ui/dom";
import { MaybeElementRef, unrefElement } from "@vueuse/core";
import { onUnmounted, watch } from "vue";

export type FloatingPlacement = Placement | "auto";

export type FloatingOptions = {
    placement?: FloatingPlacement;
    /** Element to point at the reference, positioned along the edge of the floating element */
    arrow?: HTMLElement;
    /** Distance from the reference (mainAxis) and shift along its edge (crossAxis), in pixels */
    offset?: { mainAxis?: number; crossAxis?: number };
    /** On narrow screens, span the whole width above the reference instead */
    responsive?: boolean;
};

const narrowScreenWidth = 560;

/**
 * Positions the floating element next to the reference and keeps it there while the page scrolls or either element
 * resizes. The final placement goes to the data-placement attribute, as flipping can change it, and data-narrow marks
 * the full-width layout of a responsive element.
 *
 * @returns a function that stops updating the position
 */
export function positionFloating(
    reference: HTMLElement,
    floating: HTMLElement,
    options: FloatingOptions = {},
): () => void {
    async function update() {
        const narrow = options.responsive && window.innerWidth < narrowScreenWidth;
        const placement = narrow ? "top" : (options.placement ?? "bottom");

        const { x, y, ...position } = await computePosition(reference, floating, {
            placement: placement === "auto" ? undefined : placement,
            middleware: [
                offset(options.offset ?? 0),
                placement === "auto" ? autoPlacement() : flip(),
                shift({ padding: 5 }),
                options.arrow && arrow({ element: options.arrow }),
            ],
        });

        floating.dataset.placement = position.placement;
        floating.toggleAttribute("data-narrow", narrow);

        Object.assign(floating.style, {
            position: position.strategy,
            top: `${y}px`,
            left: narrow ? "0" : `${x}px`,
            right: narrow ? "0" : "",
            width: narrow ? "auto" : "",
        });

        if (options.arrow) {
            const { x: arrowX, y: arrowY } = position.middlewareData.arrow;

            if (narrow) {
                const referenceRect = reference.getBoundingClientRect();
                const floatingRect = floating.getBoundingClientRect();

                options.arrow.style.left = `${referenceRect.left + referenceRect.width / 2 - floatingRect.left}px`;
            } else {
                options.arrow.style.left = arrowX != null ? `${arrowX}px` : "";
            }

            options.arrow.style.top = arrowY != null ? `${arrowY}px` : "";
        }
    }

    return autoUpdate(reference, floating, update);
}

/**
 * Keeps the element positioned next to the reference while both exist.
 */
export function useFloating(
    reference: MaybeElementRef<HTMLElement>,
    element: MaybeElementRef<HTMLElement>,
    options: FloatingOptions = {},
): void {
    let stop: (() => void) | null = null;

    function cleanup() {
        stop?.();
        stop = null;
    }

    watch(
        [() => unrefElement(reference), () => unrefElement(element)],
        ([reference, element]) => {
            cleanup();

            if (reference && element) {
                stop = positionFloating(reference as HTMLElement, element as HTMLElement, options);
            }
        },
        { immediate: true },
    );

    onUnmounted(cleanup);
}
