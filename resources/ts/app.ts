/// <reference path="types/webpack.d.ts"/>

import '../styles/main.scss'
import "leaflet/dist/leaflet.css";

import Popper from 'popper.js';
import * as $ from "jquery";

import * as components from './components';

window['$'] = window['jQuery'] = $;
window['Popper'] = Popper;

// dependencies
import './font-awesome';
import 'bootstrap'
import { Vue } from "vue-property-decorator";

import './filters'

// async dependencies
(async function () {
})();

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