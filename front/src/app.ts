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

import { Store } from 'vuex';
import { dragscrollNext } from 'vue-dragscroll';
import { Workbox } from "workbox-window";
import { StoreDefinition } from "@/store/initializer";
import { Vue } from "vue-class-component"

import moment from "moment";

import components, { app } from "@/components";
import filters from '@/filters'

app.use(filters);
app.use(components);

app.directive("dragscroll", dragscrollNext);

declare module '@vue/runtime-core' {
    interface ComponentCustomProperties {
        $isTouch: boolean;
        $hasSlot: (slot: string) => string;
        $moment: typeof moment;
        $store: Store<StoreDefinition>;
    }
}

app.config.globalProperties.$isTouch = 'ontouchstart' in window || navigator['msMaxTouchPoints'] > 0;
app.config.globalProperties.$hasSlot = function (this: Vue, slot: string): boolean {
    return !!this.$slots[slot];
}
app.config.globalProperties.$moment = moment;

Vue.registerHooks(['removed']);

// async dependencies
(async function () {
    const { migrate } = await import('./store/migrations');
    await migrate("vuex");
    const { default: store } = await import('./store');

    app.use(store);

    // todo figure out better way
    const fragment = document.createDocumentFragment();
    const root = document.getElementById('root');
    app.mount(fragment as unknown as Element);
    root.parentNode.replaceChild(fragment, root);
})();

if ('serviceWorker' in navigator) {
    const wb = new Workbox("/service-worker.js");

    wb.addEventListener('waiting', _ => {
        if (window.confirm("Dostępna jest nowa wersja, przeładować?")) {
            wb.addEventListener('controlling', _ => {
                window.location.reload();
            });

            wb.messageSW({type: 'SKIP_WAITING'});
        }
    });

    wb.register();
}
