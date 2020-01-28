/// <reference path="types/webpack.d.ts"/>

import '../styles/main.scss'

import "leaflet/dist/leaflet.css";

import Popper from 'popper.js';
import * as $ from "jquery";

window['$'] = window['jQuery'] = $;
window['Popper'] = Popper;

// dependencies
import Vue from "vue";
import Vuex from 'vuex';
import PortalVue from 'portal-vue';
import VueDragscroll from 'vue-dragscroll';
import { Plugin as VueFragment } from 'vue-fragment';
import { Workbox } from "workbox-window";

import { migrate } from "./store/migrations";
import { Component } from "vue-property-decorator";

Vue.use(Vuex);
Vue.use(PortalVue);
Vue.use(VueDragscroll);
Vue.use(VueFragment);

declare module 'vue/types/vue' {
    interface Vue {
        $isTouch: boolean;
    }
}

Vue.prototype.$isTouch = 'ontouchstart' in window || navigator.msMaxTouchPoints > 0;

Component.registerHooks(['removed']);

// async dependencies
(async function () {
    await migrate("vuex");

    const [ components, { default: store } ] = await Promise.all([
        import('./components'),
        import('./store'),
        import('./font-awesome'),
        import('./filters'),
        import('bootstrap'),
    ] as const);

    // here goes "public" API
    window['czydojade'] = Object.assign({
        state: {}
    }, window['czydojade'], {
        components,
        application: new components.Application({ el: '#app' })
    });

    store.dispatch('messages/update');
    store.dispatch('load', window['czydojade'].state);

    if ('serviceWorker' in navigator) {
        const wb = new Workbox("/service-worker.js");

        wb.addEventListener('waiting', event => {
            if (window.confirm("Dostępna jest nowa wersja, przeładować?")) {
                wb.addEventListener('controlling', event => {
                    window.location.reload();
                });

                wb.messageSW({type: 'SKIP_WAITING'});
            }
        });

        wb.register();
    }
})();
