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

import { MakeOptional, Supplier } from "@/utils";
import { UrlParams } from "@/api/utils";
import { EndpointCollection, EndpointParams, EndpointResult, Endpoints } from "@/api/endpoints";
import { AxiosResponse } from "axios";
import store from "@/store";

export type RequestOptions<TParams extends {}> = {
    version: string,
    query?: Supplier<string | UrlParams>,
    headers?: Supplier<{ [name: string]: string }>,
} & ({} extends TParams ? { params?: Supplier<TParams> } : { params: Supplier<TParams> })

export type BoundRequestOptions<TParams extends EndpointParams<any, any>, TBoundParams extends string>
    = RequestOptions<MakeOptional<TParams, keyof TParams & TBoundParams>>

export interface ApiClient<TEndpoints extends EndpointCollection, TBoundParams extends string = never> {
    get<TEndpoint extends keyof TEndpoints>(
        endpoint: TEndpoint,
        options: BoundRequestOptions<EndpointParams<TEndpoints, TEndpoint>, TBoundParams>,
    ): Promise<AxiosResponse<EndpointResult<TEndpoints, TEndpoint>>>;
}

export const client: ApiClient<Endpoints, "provider"> = store.$api;

export default client;
