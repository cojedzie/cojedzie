import { set, signed } from "./utils";
import Vue from 'vue';
import { condition } from "./decorators";

export const defaultBreakpoints = {
    'xs': 0,
    'sm': 576,
    'md': 768,
    'lg': 1024,
    'xl': 1200,
}

Vue.filter('signed', signed);

Vue.directive('hover',  {
    bind(el, binding, node) {
        const update = (hovered: boolean, e: Event) => {
            if (typeof binding.value === 'function') {
                binding.value(hovered, e);
            }

            if (typeof binding.value === 'boolean') {
                set(node.context, binding.expression, hovered);
            }

            if (typeof binding.arg !== 'undefined') {
                set(node.context, binding.arg, hovered);
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
    unbind(el, binding) {
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

Vue.directive('autofocus', {
   inserted(el, binding) {
       if (binding.value !== undefined) {
           const value = binding.value;

           if ((typeof value === "boolean" && !value) || (typeof value === "function" && !value(el))) {
               return;
           }
       }

       el.focus();
   }
});

Vue.directive('responsive', {
    inserted(el, binding) {
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
        resize();

        if (!binding.modifiers['once']) {
            window.addEventListener('resize', resize);
        }
    },
    unbind(el, binding) {
        if (typeof binding['resize'] !== 'undefined') {
            window.removeEventListener('resize', binding['resize']);
        }
    }
});
