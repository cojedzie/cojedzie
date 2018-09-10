/// <reference path="types/webpack.d.ts"/>

import '../styles/main.scss'

import './font-awesome'
import './filters'

import Popper from 'popper.js';
import * as $ from "jquery";

import * as components from './components';

window['$'] = window['jQuery'] = $;
window['Popper'] = Popper;

// dependencies
import 'bootstrap'
import Vue from 'vue';

// here goes "public" API
window['czydojade'] = {
    components
};

window['app'] = new Vue({ el: '#app' });