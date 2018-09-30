/// <reference path="types/webpack.d.ts"/>

import '../styles/main.scss'
import "leaflet/dist/leaflet.css";

import Popper from 'popper.js';
import * as $ from "jquery";

window['$'] = window['jQuery'] = $;
window['Popper'] = Popper;

// dependencies
import Vue from "vue";
import Vuex, { mapActions, mapState, Store } from 'vuex';

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

    // here goes "public" API
    window['czydojade'] = {
        components
    };

    window['app'] = new Vue({
        el: '#app',
        store: store,
        data: {
            stops: [],
            sections: {
                messages: true
            }
        },
        computed: {
            messages(this: any) {
                return {
                    count:  this.$store.getters['messages/count'],
                    counts: this.$store.getters['messages/counts'],
                    state:  this.$store.state.messages.state
                };
            },
            departures(this: any) {
                return {
                    state: this.$store.state.departures.state
                };
            }
        },
        watch: {
            stops(this: any, stops) {
                this.updateDepartures({ stops });
            }
        },
        methods: {
            ...mapActions({
                updateMessages:   'messages/update',
                updateDepartures: 'departures/update'
            })
        },
        mounted() {
            this.$el.classList.remove('not-ready');
        }
    });
})();
