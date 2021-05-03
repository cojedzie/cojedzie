import { EndpointCollection, EndpointParams, EndpointResult } from "@/api/endpoints";
import { ApiClient, BoundRequestOptions } from "@/api/client";
import { resolve, Supplier } from "@/utils";
import { AxiosResponse } from "axios";
import { prepare } from "@/api/utils";
import { http } from "@/api/client/http";

export class StaticClient<TEndpoints extends EndpointCollection, TBoundParams extends string = never> implements ApiClient<TEndpoints, TBoundParams> {
    private readonly endpoints: TEndpoints;
    private readonly bound: Supplier<{ [name in TBoundParams]: string }>;

    constructor(endpoints: TEndpoints, bound?: Supplier<{ [name in TBoundParams]: string }>) {
        this.endpoints = endpoints;
        this.bound = bound;
    }

    async get<TEndpoint extends keyof TEndpoints>(
        endpoint: TEndpoint,
        options: BoundRequestOptions<EndpointParams<TEndpoints, TEndpoint>, TBoundParams>,
    ): Promise<AxiosResponse<EndpointResult<TEndpoints, TEndpoint>>> {
        const url = prepare(
            this.endpoints[endpoint].template,
            {
                ...(resolve(this.bound) || {}),
                ...(resolve(options.params) || {})
            },
        );

        return await http.get(url, {
            params: resolve(options.query),
            headers: resolve(options.headers),
        });
    }
}
