import { EndpointCollection, EndpointParams, EndpointResult } from "@/api/endpoints";
import { ApiClient, BoundRequestOptions } from "@/api/client";
import { resolve, Supplier } from "@/utils";
import { AxiosResponse } from "axios";
import { prepare } from "@/api/utils";
import { http } from "@/api/client/http";

export type StaticRequestOptions<
    TEndpoints extends EndpointCollection,
    TEndpoint extends keyof TEndpoints,
    TBoundParams extends string
> = BoundRequestOptions<EndpointParams<TEndpoints, TEndpoint>, TBoundParams> & {
    base?: Supplier<string>
};

export class StaticClient<TEndpoints extends EndpointCollection, TBoundParams extends string = never> implements ApiClient<TEndpoints, TBoundParams> {
    private readonly endpoints: TEndpoints;
    private readonly bound: Supplier<{ [name in TBoundParams]: string }>;

    constructor(endpoints: TEndpoints, bound?: Supplier<{ [name in TBoundParams]: string }>) {
        this.endpoints = endpoints;
        this.bound = bound;
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

        return await http.get(url, {
            baseURL: resolve(options.base),
            params: resolve(options.query),
            headers: resolve(options.headers),
        });
    }
}
