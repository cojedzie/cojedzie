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
import Vuex from "vuex";
import { createHttpClient, http } from "@/api/client/http";
import { AxiosRequestConfig } from "axios";
import moment, { Moment } from "moment";
import yargs from "yargs";
import { createStore } from "@/store/initializer";
import { normal } from "@/utils/random";
import { delay, distinct } from "@/utils";
import * as es from "@elastic/elasticsearch"

import * as httpModule from "http";
import * as httpsModule from "https";
import { map, merge } from "@/utils/object";
import scenario from "./scenarios/basic";

const { hideBin } = require('yargs/helpers')

Vue.use(Vuex)

type LoadtesterArgv = {
    url: string;
    concurrency: number;
    delay: number;
    progress: number;
    verbose: boolean | number;
    sigma: number;
    duration: number;
    elasticsearch: es.Client;
}

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
            type: "count",
            default: false,
            description: "Verbose logging",
            alias: 'v',
        },
        sigma: {
            type: "number",
            description: "Standard Deviation for delay (by default 1/10 of delay)",
            alias: 's',
            default: undefined,
        },
        duration: {
            type: "number",
            description: "Maximum duration for the loadtests in seconds",
            alias: 'l',
            default: undefined
        },
        elasticsearch: {
            type: "string",
            description: "Address for elasticsearch cluster",
            alias: 'e',
            default: 'http://elasticsearch:9200',
            coerce: hostname => new es.Client({ node: hostname })
        }
    })
    .argv as LoadtesterArgv

const requestStartTimes = new WeakMap<AxiosRequestConfig, Moment>()

let requestCounts: Record<string, number> = {};
let requestErrors: Record<string, number> = {};
let requestFailures: Record<string, number> = {};
let requestTimes: Record<string, number[]> = {};

let totalRequestCounts: Record<string, number> = {};
let totalRequestErrors: Record<string, number> = {};
let totalRequestFailures: Record<string, number> = {};
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

function mean(values: number[]): number {
    return values.reduce((a, b) => a + b, 0) / values.length
}

function quantile(values: number[], quantile: number) {
    return values[(values.length * quantile) | 0];
}

function sorted(values: number[]) {
    const result = Array.from(values);
    result.sort((a, b) => a - b)

    return result;
}

const elastic = argv.elasticsearch;

const runIdentifier: string = moment().toISOString();

function reportProgress() {
    const requestTimeAvgs = map(requestTimes, v => mean(v))
    const requestTimeSorted = map(requestTimes, v => sorted(v))

    const allTimes = sorted(Object.values(requestTimes).flat())

    const percentile80th = map(requestTimeSorted, v => quantile(v, .8))
    const percentile50th = map(requestTimeSorted, v => quantile(v, .5))
    const percentile95th = map(requestTimeSorted, v => quantile(v, .95))

    const errorCount = Object.values(requestErrors).reduce((a, b) => a + b, 0);
    const failCount = Object.values(requestFailures).reduce((a, b) => a + b, 0);

    console.log(`Current clients: ${runningClients} / ${argv.concurrency}`)
    argv.verbose && console.log(`Avg: ${Math.round(mean(allTimes))}; Err: ${errorCount}; Fail: ${failCount}; percentiles: 50th - ${quantile(allTimes, .5)}, 80th - ${quantile(allTimes, .8)}, 95th - ${quantile(allTimes, .95)}`)
    argv.verbose >= 2 && console.log('Request count: ', requestCounts)
    argv.verbose >= 2 && console.log('Request time avg: ', requestTimeAvgs)
    argv.verbose >= 2 && console.log('Request errors: ', requestErrors)
    argv.verbose >= 2 && console.log('50th percentile: ', percentile50th)
    argv.verbose >= 2 && console.log('80th percentile: ', percentile80th)
    argv.verbose >= 2 && console.log('95th percentile: ', percentile95th)

    totalRequestTimes = merge(totalRequestTimes, map(requestTimeAvgs, v => [ v ]), (tot, cur) => [ ...(tot || []), ...(cur || []) ]);
    totalRequestCounts = merge(totalRequestCounts, requestCounts, (tot, cur) => tot + cur);
    totalRequestErrors = merge(totalRequestErrors, requestErrors, (tot, cur) => tot + cur);
    totalRequestFailures = merge(totalRequestFailures, requestFailures, (tot, cur) => tot + cur);

    const timestamp = moment().toISOString();

    const urls = [
        ...Object.keys(requestCounts),
        ...Object.keys(requestTimes),
        ...Object.keys(requestErrors),
        ...Object.keys(requestFailures),
    ].filter(distinct)

    for (const endpoint of urls) {
        const url = new URL(endpoint);

        const elasticData = {
            '@timestamp': timestamp,
            'run_identifier': runIdentifier,
            'host': url.host,
            'path': url.pathname,
            'stats': {
                'num_requests': requestCounts[endpoint] || 0,
                'num_errors': requestErrors[endpoint] || 0,
                'num_failures': requestFailures[endpoint] || 0,
                'total_response_time': (requestTimes[endpoint] || []).reduce((a, b) => a + b, 0),
                'max_response_time': Math.max(...(requestTimes[endpoint] || [])),
                'min_response_time': Math.min(...(requestTimes[endpoint] || [])),
                'response_times': requestTimes[endpoint] || [],
                'client_count': runningClients,
            }
        }

        elastic.index({
            index: `loadtest${moment().format("YYYYMMDD")}`,
            body: elasticData
        })
    }

    requestTimes = {}
    requestCounts = {}
    requestErrors = {}
    requestFailures = {}
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
        })

        http.interceptors.request.use(requestStartTimingInterceptor);
        http.interceptors.response.use(requestEndTimingInterceptor);

        runningClients++;

        const store = createStore({
            apiClientOptions: {
                http,
                onRequestError: req => requestErrors[req.url] = ((requestErrors[req.url] || 0) + 1),
                onRequestFailure: (_, req) => (requestFailures[req.url] = (requestFailures[req.url] || 0) + 1)
            }
        });

        scenario(store);

        await delay(normal(argv.delay, typeof argv.sigma === "undefined" ? argv.delay / 10 : argv.sigma));
    }
})();

if (argv.duration) {
    setTimeout(() => {
        console.log("Time is over, ending!")
        process.exit();
    }, argv.duration * 1000)
}

function summary() {
    console.log("Run Identifier: ", runIdentifier)

    console.log("Total requests made:")
    console.log(totalRequestCounts);
}

process.on('exit', summary)

process.on('SIGINT', function () {
    console.info("Terminating clients...");
    process.exit();
});
