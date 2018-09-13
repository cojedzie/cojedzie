/// <reference path="types/webpack.d.ts"/>

import '../styles/main.scss'

import './font-awesome'
import './filters'

import "leaflet/dist/leaflet.css";

import Popper from 'popper.js';
import * as $ from "jquery";

import * as components from './components';

window['$'] = window['jQuery'] = $;
window['Popper'] = Popper;

// dependencies
import 'bootstrap'
import { Vue } from "vue-property-decorator";

// here goes "public" API
window['czydojade'] = {
    components
};

window['app'] = new Vue({
    el: '#app',
    data: {
        messages: {
            count:   0,
            visible: true
        }
    }, methods: {
        handleMessagesUpdate(messages) {
            this.messages.count = messages.length;
        }
    }
});