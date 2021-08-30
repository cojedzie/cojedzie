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

import { set, signed } from "./utils";
import { condition } from "./decorators";
import { app } from "@/components";
import moment from "moment";

export const defaultBreakpoints = {
    'xs': 0,
    'sm': 576,
    'md': 768,
    'lg': 1024,
    'xl': 1200,
}

app.config.globalProperties.$f = {
    signed,
    duration: (...args) => moment.duration(...args)
}

app.directive('hover',  {
    beforeMount(el, binding, node) {
        const update = (hovered: boolean, e: Event) => {
            if (typeof binding.value === 'function') {
                binding.value(hovered, e);
            }

            // fixme, vue3 removed expression
            // if (typeof binding.value === 'boolean') {
            //     set(binding.instance, binding.expression, hovered);
            // }

            if (typeof binding.arg !== 'undefined') {
                set(binding.instance, binding.arg, hovered);
            }
        };

        const activate   = event => update(true, event);
        const deactivate = event => update(false, event);
        const keyboard   = condition.decorate(deactivate, e => e.keyCode == 27);

        binding['events'] = { activate, deactivate, keyboard };

        el.addEventListener('mouseenter', activate);
        el.addEventListener('click', activate);
        el.addEventListener('keydown', keyboard);
        el.addEventListener('mouseleave', deactivate);
        // el.addEventListener('focusout', deactivate);
    },
    unmounted(el, binding) {
        if (typeof binding['events'] !== 'undefined') {
            const { activate, deactivate, keyboard } = binding['events'];

            el.removeEventListener('mouseenter', activate);
            el.removeEventListener('click', activate);
            el.removeEventListener('keydown', keyboard);
            el.removeEventListener('mouseleave', deactivate);
            // el.removeEventListener('focusout', deactivate);
        }
    }
});

app.directive('autofocus', {
   mounted(el, binding) {
       if (binding.value !== undefined) {
           const value = binding.value;

           if ((typeof value === "boolean" && !value) || (typeof value === "function" && !value(el))) {
               return;
           }
       }

       el.focus();
   }
});

app.directive('responsive', {
    mounted(el, binding) {
        const breakpoints = typeof binding.value === 'object' ? binding.value : defaultBreakpoints;

        const resize = binding['resize'] = () => {
            const width = el.scrollWidth;
            el.classList.remove(...Object.keys(breakpoints).map(breakpoint => `size-${breakpoint}`));

            for (let [ breakpoint, size ] of Object.entries(breakpoints)) {
                if (width < size) {
                    break;
                }

                el.classList.add(`size-${breakpoint}`);
            }
        };
        setTimeout(() => resize());

        if (!binding.modifiers['once']) {
            window.addEventListener('resize', resize);
        }
    },
    unmounted(el, binding) {
        if (typeof binding['resize'] !== 'undefined') {
            window.removeEventListener('resize', binding['resize']);
        }
    }
});
