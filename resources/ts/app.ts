/// <reference path="types/webpack.d.ts"/>

import '../styles/main.scss'
import "leaflet/dist/leaflet.css";

import Popper from 'popper.js';
import * as $ from "jquery";

window['$'] = window['jQuery'] = $;
window['Popper'] = Popper;

// dependencies
import Vue from "vue";
import Vuex, { mapActions, mapMutations, mapState, Store } from 'vuex';

Vue.use(Vuex);

// async dependencies
(async function () {
    const [ components, { default: store } ] = await Promise.all([
        import('./components'),
        import('./store'),
        import('./font-awesome'),
        import('./filters'),
        import('bootstrap'),
    ]);

    store.dispatch('messages/update');
    store.dispatch('load', window['czydojade'].state);

    // here goes "public" API
    window['czydojade'] = Object.assign({}, window['czydojade'], {
        components, application: new components.Application({ el: '#app' })
    });
})();
