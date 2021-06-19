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
import { createHttpClient, http } from "@/api/client/http";
import { AxiosRequestConfig } from "axios";
import moment, { Moment } from "moment";
import yargs from "yargs";
import { createStore, StoreDefinition } from "@/store/initializer";
import { choice, choices, normal } from "@/utils/random";
import { delay } from "@/utils";
import * as es from "@elastic/elasticsearch"

import * as httpModule from "http";
import * as httpsModule from "https";
import { map, merge } from "@/utils/object";

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
        progress: {
            type: "number",
            default: 5000,
            description: "Interval between reporting progress to output",
            alias: 'p',
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

let requestCounts: Record<string, number> = {};
let requestTimes: Record<string, number[]> = {};

let totalRequestCounts: Record<string, number> = {};
let totalRequestTimes: Record<string, number[]> = {};

let runningClients = 0;

const requestStartTimingInterceptor = req => {
    requestStartTimes.set(req, moment());
    return req;
}

const requestEndTimingInterceptor = res => {
    const startTime = requestStartTimes.get(res.config);
    const endTime   = moment();

    const duration = endTime.diff(startTime, "milliseconds");

    const fullUrl = res.config.baseURL + res.config.url;

    requestCounts[fullUrl] = (requestCounts[fullUrl] || 0) + 1;

    if (typeof requestTimes[fullUrl] === "undefined") {
        requestTimes[fullUrl] = [ duration ];
    } else {
        requestTimes[fullUrl].push(duration);
    }

    return res;
}

http.defaults.httpAgent = new httpModule.Agent({ keepAlive: true });
http.defaults.httpsAgent = new httpsModule.Agent({ keepAlive: true });

http.defaults.baseURL = argv.url;

const possibleStopQueries = [
    'Cieszy',
    'Wilan',
    'Plac Komo',
    'Dworzec Gł',
    'Łosto',
    'Żabian',
    'Uniwers',
    'Kope',
]

async function scenario(store: Store<StoreDefinition>) {
    await store.dispatch('loadProvider', { provider: 'trojmiasto' });

    const stops = await store.$api.get('v1_stop_list', {
        version: '^1.0',
        query: { name: choice(possibleStopQueries) }
    })

    store.commit('add', choices(stops.data, (Math.random() * 3) | 0))

    const departuresUpdate = async () => {
        await store.dispatch('departures/update');
        setTimeout(departuresUpdate, 20000);
    }

    const messagesUpdate = async () => {
        await store.dispatch('messages/update');
        setTimeout(messagesUpdate, 20000);
    }

    departuresUpdate();
    messagesUpdate();
}

function mean(values: number[]): number {
    return values.reduce((a, b) => a + b) / values.length
}

function quantile(values: number[], quantile: number) {
    return values[(values.length * quantile) | 0];
}

function sorted(values: number[]) {
    const result = Array.from(values);
    result.sort((a, b) => a - b)

    return result;
}

function reportProgress() {
    const requestTimeAvgs = map(requestTimes, v => mean(v))
    const requestTimeSorted = map(requestTimes, v => sorted(v))

    const percentile80th = map(requestTimeSorted, v => quantile(v, .8))
    const percentile50th = map(requestTimeSorted, v => quantile(v, .5))
    const percentile95th = map(requestTimeSorted, v => quantile(v, .95))

    console.log(`Running clients: ${runningClients}`)
    console.log('Request count: ', requestCounts)
    console.log('Request time avg: ', requestTimeAvgs)
    console.log('50th percentile: ', percentile50th)
    console.log('80th percentile: ', percentile80th)
    console.log('95th percentile: ', percentile95th)

    totalRequestCounts = merge(totalRequestCounts, requestCounts, (tot, cur) => tot + cur);
    totalRequestTimes = merge(totalRequestTimes, map(requestTimeAvgs, v => [ v ]), (tot, cur) => [ ...(tot || []), ...(cur || []) ]);

    const timestamp = moment().toISOString();
    for (const [endpoint, count] of Object.entries(requestCounts)) {
        const url = new URL(endpoint);

        const elasticData = {
            '@timestamp': timestamp,
            'host': url.host,
            'path': url.pathname,
            'stats': {
                'num_requests': count,
                'num_failures': 0,
                'total_response_time': requestTimes[endpoint].reduce((a, b) => a + b),
                'max_response_time': Math.max(...requestTimes[endpoint]),
                'min_response_time': Math.min(...requestTimes[endpoint]),
                'response_times': requestTimes[endpoint],
            }
        }
    }

    requestTimes = {}
    requestCounts = {}
}

http.interceptors.request.use(requestStartTimingInterceptor);
http.interceptors.response.use(requestEndTimingInterceptor);

console.log(`Starting ${argv.concurrency} clients on address ${argv.url}`);

setInterval(reportProgress, argv.progress);

const httpAgent = new httpModule.Agent({ keepAlive: true });
const httpsAgent = new httpsModule.Agent({ keepAlive: true });

(async () => {
    for (let i = 0; i < argv.concurrency; i++) {

        const http = createHttpClient({
            httpAgent,
            httpsAgent,
            baseURL: argv.url,
            timeout: 30000,
        })

        http.interceptors.request.use(requestStartTimingInterceptor);
        http.interceptors.response.use(requestEndTimingInterceptor);

        runningClients++;

        const store = createStore({ http });
        scenario(store);

        await delay(normal(argv.delay, typeof argv.sigma === "undefined" ? argv.delay / 10 : argv.sigma));
    }
})();

process.on('SIGINT', function() {
    console.log(totalRequestCounts);

    console.info("Terminating clients...");
    process.exit();
});
