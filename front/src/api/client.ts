import { EndpointParams, EndpointResult, endpoints, Endpoints } from "@/api/endpoints";
import { MakeOptional, Supplier } from "@/utils";
import axios, { AxiosResponse } from "axios";
import store from "@/store";
import { prepare, query, UrlParams } from "@/api/utils";

export type RequestOptions<TParams extends {}> = {
    version: string,
    query?: Supplier<string | UrlParams>,
    headers?: { [name: string]: string },
} & ({} extends TParams ? { params?: Supplier<TParams> } : { params: Supplier<TParams> })

export type BoundRequestOptions<TParams extends EndpointParams<any, any>, TBoundParams extends string>
    = RequestOptions<MakeOptional<TParams, keyof TParams & TBoundParams>>

export interface ApiClient<TEndpoints extends Endpoints, TBoundParams extends string> {
    get<TEndpoint extends keyof TEndpoints>(
        endpoint: TEndpoint,
        options: BoundRequestOptions<EndpointParams<TEndpoints, TEndpoint>, TBoundParams>,
    ): Promise<AxiosResponse<EndpointResult<TEndpoints, TEndpoint>>>;
}

const http = axios.create({
    paramsSerializer: query,
});

export const client: ApiClient<Endpoints, "provider"> = {
    async get<TEndpoint extends keyof Endpoints>(
        endpoint: TEndpoint,
        options: BoundRequestOptions<EndpointParams<Endpoints, TEndpoint>, "provider">,
    ): Promise<AxiosResponse<EndpointResult<Endpoints, TEndpoint>>> {
        const url = prepare(
            endpoints[endpoint].template,
            { provider: store.state.provider?.id, ...(options.params || {}) },
        );

        return await http.get(url, {
            params: options.query,
            headers: options.headers,
        });
    }
}

export default client;
