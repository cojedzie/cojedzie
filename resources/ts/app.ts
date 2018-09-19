/// <reference path="types/webpack.d.ts"/>

import '../styles/main.scss'
import "leaflet/dist/leaflet.css";

import Popper from 'popper.js';
import * as $ from "jquery";

window['$'] = window['jQuery'] = $;
window['Popper'] = Popper;

// dependencies
import { Vue } from "vue-property-decorator";

// async dependencies
(async function () {
    const [ components ] = await Promise.all([
        import('./components'),
        import('./font-awesome'),
        import('./filters'),
        import('bootstrap'),
    ]);

    // here goes "public" API
    window['czydojade'] = {
        components
    };

    window['app'] = new Vue({
        el: '#app',
        data: {
            stops: [],
            messages: {
                count:   0,
                visible: true
            },
            departures: {
                state: ''
            }
        }, methods: {
            handleMessagesUpdate(messages) {
                this.messages.count = messages.length;
            }
        }
    });
})();
