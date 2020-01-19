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
import { Workbox } from "workbox-window";

Vue.use(Vuex);
Vue.use(PortalVue);

// async dependencies
(async function () {
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
