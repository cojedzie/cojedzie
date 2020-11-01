/// <reference path="types/webpack.d.ts"/>

import '../styles/main.scss'

import "leaflet/dist/leaflet.css";

import Popper from 'popper.js';
import * as $ from "jquery";
// dependencies
import Vue from "vue";
import Vuex from 'vuex';
import PortalVue from 'portal-vue';
import VueDragscroll from 'vue-dragscroll';
import { Plugin as VueFragment } from 'vue-fragment';
import { Workbox } from "workbox-window";

import { Component } from "vue-property-decorator";
import * as VueMoment from "vue-moment";
import * as moment from 'moment';
import 'moment/locale/pl'

window['$'] = window['jQuery'] = $;
window['Popper'] = Popper;

Vue.use(Vuex);
Vue.use(PortalVue);
Vue.use(VueDragscroll);
Vue.use(VueFragment);
Vue.use(VueMoment, { moment });

declare module 'vue/types/vue' {
    interface Vue {
        $isTouch: boolean;
        $hasSlot: (slot: string) => string;
    }
}

Vue.prototype.$isTouch = 'ontouchstart' in window || navigator.msMaxTouchPoints > 0;
Vue.prototype.$hasSlot = function (this: Vue, slot: string): boolean {
    return !!this.$slots[slot] || !!this.$scopedSlots[slot];
}

Component.registerHooks(['removed']);

// async dependencies
(async function () {
    const { migrate } = await import('./store/migrations');

    await migrate("vuex");

    const [ components, { default: store } ] = await Promise.all([
        import('./components'),
        import('./store'),
        import('./filters'),
        import('bootstrap'),
    ] as const);

    const appRoot = document.getElementById('app');

    store.replaceState({
        ...store.state,
        provider: window['data']?.provider,
    });

    // here goes "public" API
    window['app'] = Object.assign({
        state: {}
    }, window['app'], {
        components,
        application: appRoot ? new components.Application({ el: '#app' }) : new components.PageProviderList({ el: '#provider-picker' }),
    });

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
