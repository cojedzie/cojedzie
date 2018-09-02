/// <reference path="types/popper.js.d.ts"/>
/// <reference path="types/webpack.d.ts"/>

import '../styles/main.scss'

import './font-awesome'
import './filters'

import * as Popper from 'popper.js';
import * as $ from "jquery";

import * as components from './components';

window['$'] = window['jQuery'] = $;
window['popper'] = Popper;

// dependencies
import 'bootstrap'
import Vue from 'vue';

// here goes "public" API
window['czydojade'] = {
    components
};

window['app'] = new Vue({ el: '#app' });