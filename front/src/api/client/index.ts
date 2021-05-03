import { MakeOptional, Supplier } from "@/utils";
import { UrlParams } from "@/api/utils";
import { EndpointCollection, EndpointParams, EndpointResult } from "@/api/endpoints";
import { AxiosResponse } from "axios";
import loadbalancer from "@/api/loadbalancer";
import { LoadBalancedClient } from "@/api/client/balanced";
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

export const client = new LoadBalancedClient(loadbalancer, () => ({ provider: store.state.provider?.id }))

export default client;
