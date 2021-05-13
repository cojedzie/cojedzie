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

import "module-alias/register";

import Vue from "vue";
import Vuex, { Store } from "vuex";
import { http } from "@/api/client/http";
import { AxiosRequestConfig } from "axios";
import moment, { Moment } from "moment";
import yargs from "yargs";
import { RootState } from "@/store/root";
import { normal } from "@/utils/random";
import { delay } from "@/utils";

const { hideBin } = require('yargs/helpers')

Vue.use(Vuex)

const argv = yargs(hideBin(process.argv))
    .option({
        url: {
            type: 'string',
            default: "https://cojedzie.pl",
            description: "Base URL for the load tests",
            alias: 'u',
        },
        concurrency: {
            type: "number",
            default: 50,
            description: "Number of concurrent clients to use",
            alias: 'c',
        },
        delay: {
            type: "number",
            default: 1500,
            description: "Mean delay between client initializations",
            alias: 'd',
        },
        verbose: {
            type: "boolean",
            default: false,
            description: "Verbose logging",
            alias: 'v',
        },
        sigma: {
            type: "number",
            description: "Standard Deviation for delay (by default 1/10 of delay)",
            alias: 's',
            default: undefined,
        }
    })
    .argv

const requestStartTimes = new WeakMap<AxiosRequestConfig, Moment>()

http.defaults.baseURL = argv.url;
http.interceptors.request.use(req => {
    requestStartTimes.set(req, moment());
    return req;
})
http.interceptors.response.use(res => {
    const startTime = requestStartTimes.get(res.config);
    const endTime   = moment();

    console.log(endTime.diff(startTime), res.config.url);
    return res;
})

async function scenario(store: Store<RootState>) {
    await store.dispatch('loadProvider', { provider: 'trojmiasto' });

    const departuresUpdate = async () => {
        await store.dispatch('departures/update');
        setTimeout(departuresUpdate, 10000);
    }

    departuresUpdate();
}

console.log(`Starting ${argv.concurrency} clients on address ${argv.url}`)
import('@/store').then(async ({ createStore }) => {
    for (let i = 0; i < argv.concurrency; i++) {
        const store = createStore();
        await scenario(store);

        await delay(normal(argv.delay, typeof argv.sigma === "undefined" ? argv.delay / 10 : argv.sigma));
    }
})

process.on('SIGINT', function() {
    console.info("Terminating clients...");
    process.exit();
});
