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
import { ApiClient, BoundRequestOptions } from "@/api/client";
import { LoadBalancedEndpoint, LoadBalancer } from "@/api/loadbalancer";
import { delay, resolve, Supplier } from "@/utils";
import { AxiosResponse } from "axios";
import { prepare } from "@/api/utils";
import { http } from "@/api/client/http";
import semver from "semver";
import store from "@/store";
import { NetworkActions } from "@/store/network";

export type LoadBalancedRequestOptions<
    TEndpoints extends EndpointCollection,
    TEndpoint extends keyof TEndpoints,
    TBoundParams extends string
> = BoundRequestOptions<EndpointParams<TEndpoints, TEndpoint>, TBoundParams> & {
    require?: (candidate: LoadBalancedEndpoint<TEndpoints, TEndpoint>) => boolean
};

export class LoadBalancedClient<TEndpoints extends EndpointCollection, TBoundParams extends string = never> implements ApiClient<TEndpoints, TBoundParams> {
    private readonly balancer: LoadBalancer<TEndpoints>;
    private readonly bound: Supplier<{ [name in TBoundParams]: string }>;

    constructor(balancer: LoadBalancer<TEndpoints>, bound?: Supplier<{ [name in TBoundParams]: string }>) {
        this.bound = bound;
        this.balancer = balancer;
    }

    async get<TEndpoint extends keyof TEndpoints>(
        endpoint: TEndpoint,
        options: LoadBalancedRequestOptions<TEndpoints, TEndpoint, TBoundParams>,
    ): Promise<AxiosResponse<EndpointResult<TEndpoints, TEndpoint>>> {
        let retry = 0;
        while (retry < 5) {
            if (retry > 0) {
                console.warn(`Retrying (${retry}) calling ${endpoint}.`)
            }

            const definition = await this.balancer.get(endpoint, {
                require: candidate =>
                    semver.satisfies(semver.coerce(candidate.version), options.version) &&
                    (!options.require || options.require(candidate))
            });

            const url = prepare(
                definition.template,
                {
                    ...(resolve(this.bound) || {}),
                    ...(resolve(options.params) || {})
                },
            );

            try {
                return await http.get(url, {
                    baseURL: definition.node?.url,
                    params: resolve(options.query),
                    headers: resolve(options.headers),
                });
            } catch (err) {
                if (definition.node) {
                    await store.dispatch(`network/${NetworkActions.NodeFailed}`, definition.node.id)
                } else {
                    console.error(err.message);
                }
                retry++;

                await delay(3000 * (retry - 1));
            }
        }
    }
}
