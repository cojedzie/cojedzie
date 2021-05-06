import { choice, Optionalify } from "@/utils";
import endpoints, { EndpointCollection, Endpoints } from "@/api/endpoints";
import store from "@/store";
import { NetworkActions } from "@/store/network";

export interface LoadBalancerNode<TEndpoints extends EndpointCollection> {
    id: string;
    url: string;
    endpoints: Optionalify<Endpoints>,
}

export type LoadBalancedEndpoint<TEndpoints extends EndpointCollection, TEndpoint extends keyof TEndpoints> = {
    node: LoadBalancerNode<TEndpoints> | null;
} & TEndpoints[TEndpoint];

export interface LoadBalanceOptions<TEndpoints extends EndpointCollection, TEndpoint extends keyof TEndpoints> {
    require: (candidate: LoadBalancedEndpoint<TEndpoints, TEndpoint>) => boolean,
}

export interface LoadBalancer<TEndpoints extends EndpointCollection> {
    get<TEndpoint extends keyof TEndpoints>(
        name: TEndpoint,
        options: LoadBalanceOptions<TEndpoints, TEndpoint>
    ): Promise<LoadBalancedEndpoint<TEndpoints, TEndpoint>>,

    candidates<TEndpoint extends keyof TEndpoints>(
        name: TEndpoint,
        options: LoadBalanceOptions<TEndpoints, TEndpoint>
    ): Promise<LoadBalancedEndpoint<TEndpoints, TEndpoint>[]>,
}

export class LoadBalancerImplementation<TEndpoints extends EndpointCollection> implements LoadBalancer<TEndpoints> {
    private updateNodesTimeout: number;
    private fallback: TEndpoints;

    constructor(fallback: TEndpoints) {
        this.fallback = fallback;

        this.updateNodes();
        this.updateNodesTimeout = window.setInterval(() => this.updateNodes(), 60000);
    }


    private async updateNodes() {
        await store.dispatch({ type: `network/${NetworkActions.Update}` });
    }

    async candidates<TEndpoint extends keyof TEndpoints>(
        name: TEndpoint,
        options: LoadBalanceOptions<TEndpoints, TEndpoint>
    ): Promise<LoadBalancedEndpoint<TEndpoints, TEndpoint>[]> {
        const requirements = options.require || (endpoint => true)

        return (store.getters['network/available'] as LoadBalancerNode<TEndpoints>[])
            .filter(node => typeof node.endpoints[name as string] !== "undefined")
            .map<LoadBalancedEndpoint<TEndpoints, TEndpoint>>(node => ({
                node,
                ...node.endpoints[name as string],
            }))
            .filter(endpoint => requirements(endpoint))
    }

    async get<TEndpoint extends keyof TEndpoints>(
        name: TEndpoint,
        options: LoadBalanceOptions<TEndpoints, TEndpoint>
    ): Promise<LoadBalancedEndpoint<TEndpoints, TEndpoint>> {
        const candidates = await this.candidates(name, options);

        if (candidates.length === 0) {
            return { ...this.fallback[name], node: null };
        }

        return choice(candidates);
    }
}

export const loadbalancer = new LoadBalancerImplementation(endpoints);

export default loadbalancer;
