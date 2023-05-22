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

import 'vite/modulepreload-polyfill';

import '@styles/main.scss'

import { Store } from 'vuex';
import { dragscrollNext } from 'vue-dragscroll';
import { StoreDefinition } from "@/store/initializer";
import { Vue } from "vue-class-component"
import * as Sentry from "@sentry/vue";

import moment from "moment";
import 'moment/dist/locale/pl'

moment.locale('pl')

import components, { app } from "@/components";
import filters from '@/filters'
import globals from '@/globals'
import { install as api } from '@/api';
import container from '@/services'
import { router } from "@/routes";

if (window.CoJedzie.sentry.dsn) {
    Sentry.init({
        app,
        dsn: window.CoJedzie.sentry.dsn,

        integrations: [
            new Sentry.BrowserTracing({
                routingInstrumentation: Sentry.vueRouterInstrumentation(router),
                tracePropagationTargets: [window.CoJedzie.api.base, window.CoJedzie.api.hub, /^\//]
            }),
            new Sentry.Replay(),
        ],

        release: window.CoJedzie.version,
        environment: window.CoJedzie.sentry.environment,

        tracesSampleRate: window.CoJedzie.sentry.tracesSampleRate || 0.1,

        replaysSessionSampleRate: window.CoJedzie.sentry.replaysSessionSampleRate || 0.1,
        replaysOnErrorSampleRate: 1.0,
    });
}

app.use(api);
app.use(filters);
app.use(components);
app.use(globals);
app.use(router);
app.directive("dragscroll", dragscrollNext);

declare module '@vue/runtime-core' {
    interface ComponentCustomProperties {
        $isTouch: boolean;
        $hasSlot: (slot: string) => string;
        $moment: typeof moment;
        $store: Store<StoreDefinition>;
    }
}

Vue.registerHooks(['removed']);

// async dependencies
(async function () {
    const { migrate } = await import('./store/migrations');
    await migrate("vuex");
    const { default: store } = await import('./store');

    app.use(store);
    app.use(container)

    // todo figure out better way
    const fragment = document.createDocumentFragment();
    const root = document.getElementById('root');
    app.mount(fragment as unknown as Element);
    root.parentNode.replaceChild(fragment, root);
})();
