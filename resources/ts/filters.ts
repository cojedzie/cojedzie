import { signed } from "./utils";
import Vue from 'vue';

Vue.filter('signed', signed);
Vue.directive('hover', (el, binding) => {
    el.addEventListener('mouseenter', binding.value);
    el.addEventListener('mouseleave', binding.value);
});