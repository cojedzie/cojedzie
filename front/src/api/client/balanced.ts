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

import { EndpointCollection, EndpointParams, EndpointResult } from "@/api/endpoints";
import {
    ApiClient,
    ApiClientOptions,
    ApiClientRequestInfo,
    ApiClientStartRequestEventHandler,
    BoundRequestOptions,
    RequestOptions
} from "@/api/client";
import { LoadBalancedEndpoint, LoadBalancer } from "@/api/loadbalancer";
import { delay, resolve, Supplier } from "@/utils";
import { AxiosInstance, AxiosResponse } from "axios";
import { prepare } from "@/api/utils";
import { http as globalHttpClient } from "@/api/client/http";
import semver from "semver";
import { NetworkActions } from "@/store/modules/network";
import { Store } from "vuex";

export type LoadBalancedRequestOptions<
    TEndpoints extends EndpointCollection,
    TEndpoint extends keyof TEndpoints,
    TBoundParams extends string
> = BoundRequestOptions<EndpointParams<TEndpoints, TEndpoint>, TBoundParams> & {
    require?: (candidate: LoadBalancedEndpoint<TEndpoints, TEndpoint>) => boolean
};

export interface LoadBalancedClientOptions<
    TEndpoints extends EndpointCollection,
    TBoundParams extends string = never
> extends ApiClientOptions<TEndpoints, TBoundParams> {
    balancer: LoadBalancer<TEndpoints>
    store: Store<any> // eslint-disable-line @typescript-eslint/no-explicit-any
    maxRetries?: number

    onRequestError?: ApiClientStartRequestEventHandler<TEndpoints>
}

const loadBalancedClientDefaultOptions = {
    maxRetries: 5,
}

export class LoadBalancedClient<TEndpoints extends EndpointCollection, TBoundParams extends string = never> implements ApiClient<TEndpoints, TBoundParams> {
    private readonly balancer: LoadBalancer<TEndpoints>;
    private readonly bound: Supplier<{ [name in TBoundParams]: string }>;
    private readonly store: Store<any>; // eslint-disable-line @typescript-eslint/no-explicit-any
    private readonly http: AxiosInstance;
    private readonly options: LoadBalancedClientOptions<TEndpoints, TBoundParams>;

    constructor(options: LoadBalancedClientOptions<TEndpoints, TBoundParams>) {
        options = { ...loadBalancedClientDefaultOptions, ...options}

        this.options = options;

        this.bound = options.bound;
        this.balancer = options.balancer;
        this.store = options.store;
        this.http = options.http || globalHttpClient;
    }

    async get<TEndpoint extends keyof TEndpoints>(
        endpoint: TEndpoint,
        options: LoadBalancedRequestOptions<TEndpoints, TEndpoint, TBoundParams>,
    ): Promise<AxiosResponse<EndpointResult<TEndpoints, TEndpoint>>> {
        let retry = 0;
        while (retry < this.options.maxRetries) {
            const definition = await this.balancer.get(endpoint, {
                require: candidate =>
                    semver.satisfies(semver.coerce(candidate.version), options.version) &&
                    (!options.require || options.require(candidate))
            });

            const params  = {
                ...(resolve(this.bound) || {}),
                ...(resolve(options.params) || {})
            }

            const url = prepare(
                definition.template,
                params,
            );

            const requestInfo: ApiClientRequestInfo<TEndpoints, keyof TEndpoints> = {
                endpoint: endpoint,
                url: (definition.node?.url || this.http.defaults.baseURL) + url,
                options: {
                    ...options,
                    params
                } as RequestOptions<EndpointParams<TEndpoints, keyof TEndpoints>>
            };

            try {
                this.options.onRequestStart?.(requestInfo);

                const result = await this.http.get(url, {
                    baseURL: definition.node?.url || this.http.defaults.baseURL,
                    params: resolve(options.query),
                    headers: resolve(options.headers),
                });

                this.options.onRequestSuccess?.(result, requestInfo);
                this.options.onRequestEnd?.(result, requestInfo);

                return result;
            } catch (err) {
                if (definition.node) {
                    await this.store.dispatch(`network/${NetworkActions.NodeFailed}`, definition.node.id)
                } else {
                    console.error(err.message);
                    this.options.onRequestFailure?.(err, requestInfo);
                }

                this.options.onRequestError?.(requestInfo);

                if (retry++ < this.options.maxRetries) {
                    await delay(3000 * (retry - 1));
                } else {
                    this.options.onRequestFailure?.(err, requestInfo);
                    this.options.onRequestEnd?.(err, requestInfo);
                }
            }
        }
    }
}
