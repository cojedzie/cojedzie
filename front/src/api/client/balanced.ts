import { EndpointCollection, EndpointParams, EndpointResult } from "@/api/endpoints";
import { ApiClient, BoundRequestOptions } from "@/api/client";
import { LoadBalancer } from "@/api/loadbalancer";
import { resolve, Supplier } from "@/utils";
import { AxiosResponse } from "axios";
import { prepare } from "@/api/utils";
import { http } from "@/api/client/http";

export class LoadBalancedClient<TEndpoints extends EndpointCollection, TBoundParams extends string = never> implements ApiClient<TEndpoints, TBoundParams> {
    private readonly balancer: LoadBalancer<TEndpoints>;
    private readonly bound: Supplier<{ [name in TBoundParams]: string }>;

    constructor(balancer: LoadBalancer<TEndpoints>, bound?: Supplier<{ [name in TBoundParams]: string }>) {
        this.bound = bound;
        this.balancer = balancer;
    }

    async get<TEndpoint extends keyof TEndpoints>(
        endpoint: TEndpoint,
        options: BoundRequestOptions<EndpointParams<TEndpoints, TEndpoint>, TBoundParams>,
    ): Promise<AxiosResponse<EndpointResult<TEndpoints, TEndpoint>>> {
        const definition = await this.balancer.get(endpoint, { requirements: candidate => true });

        const url = prepare(
            definition.template,
            {
                ...(resolve(this.bound) || {}),
                ...(resolve(options.params) || {})
            },
        );

        return await http.get(url, {
            baseURL: definition.node.url,
            params: resolve(options.query),
            headers: resolve(options.headers),
        });
    }
}
