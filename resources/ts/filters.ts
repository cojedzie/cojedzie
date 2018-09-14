import { signed } from "./utils";
import Vue from 'vue';

Vue.filter('signed', signed);

Vue.directive('hover', (el, binding, node) => {
    const update = (hovered: boolean, e: Event) => {
        if (typeof binding.value === 'function') {
            binding.value(hovered, e);
        }

        if (typeof binding.value === 'boolean') {
            node.context[binding.expression] = hovered;
        }

        if (typeof binding.arg !== 'undefined') {
            node.context[binding.arg] = hovered;
        }
    };

    el.addEventListener('mouseenter', e => update(true, e));
    el.addEventListener('mouseleave', e => update(false, e));
});