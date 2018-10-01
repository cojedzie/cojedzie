import { set, signed } from "./utils";
import Vue from 'vue';
import { condition } from "./decorators";

Vue.filter('signed', signed);

Vue.directive('hover', (el, binding, node) => {
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

    el.addEventListener('mouseenter', activate);
    el.addEventListener('click', activate);
    el.addEventListener('keydown', condition.decorate(deactivate, e => e.keyCode == 27));
    el.addEventListener('mouseleave', deactivate);
    el.addEventListener('focusout', deactivate);
});

Vue.directive('responsive', (el, binding) => {
    const breakpoints = typeof binding.value === 'object' ? binding.value : {
        'xs': 0,
        'sm': 576,
        'md': 768,
        'lg': 1024,
        'xl': 1200,
    };

    const resize = () => {
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
});
