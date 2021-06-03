/*
 * Copyright (C) 2021 Kacper Donat
 *
 * @author Kacper Donat <kacper@kadet.net>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

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
import VueRouter from "vue-router";

window['$'] = window['jQuery'] = $;
window['Popper'] = Popper;

Vue.use(Vuex);
Vue.use(PortalVue);
Vue.use(VueDragscroll);
Vue.use(VueFragment);
Vue.use(VueMoment, { moment });
Vue.use(VueRouter);

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

    const application = new components.Application().$mount("#root")

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
