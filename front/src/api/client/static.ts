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
import { ApiClient, ApiClientOptions, BoundRequestOptions } from "@/api/client";
import { resolve, Supplier } from "@/utils";
import { AxiosInstance, AxiosResponse } from "axios";
import { prepare } from "@/api/utils";
import { http as globalHttpClient } from "@/api/client/http";

export type StaticRequestOptions<
    TEndpoints extends EndpointCollection,
    TEndpoint extends keyof TEndpoints,
    TBoundParams extends string
> = BoundRequestOptions<EndpointParams<TEndpoints, TEndpoint>, TBoundParams> & {
    base?: Supplier<string>
};

export interface StaticClientOptions<
    TEndpoints extends EndpointCollection,
    TBoundParams extends string = never
> extends ApiClientOptions<TEndpoints, TBoundParams> {
    endpoints: TEndpoints
}

export class StaticClient<TEndpoints extends EndpointCollection, TBoundParams extends string = never> implements ApiClient<TEndpoints, TBoundParams> {
    private readonly endpoints: TEndpoints;
    private readonly bound: Supplier<{ [name in TBoundParams]: string }>;
    private readonly http: AxiosInstance

    constructor({ endpoints, bound, http = globalHttpClient }: StaticClientOptions<TEndpoints, TBoundParams>) {
        this.endpoints = endpoints;
        this.bound     = bound;
        this.http      = http;
    }

    async get<TEndpoint extends keyof TEndpoints>(
        endpoint: TEndpoint,
        options: StaticRequestOptions<TEndpoints, TEndpoint, TBoundParams>
    ): Promise<AxiosResponse<EndpointResult<TEndpoints, TEndpoint>>> {
        const url = prepare(
            this.endpoints[endpoint].template,
            {
                ...(resolve(this.bound) || {}),
                ...(resolve(options.params) || {})
            },
        );

        return await this.http.get(url, {
            baseURL: resolve(options.base),
            params: resolve(options.query),
            headers: resolve(options.headers),
        });
    }
}
