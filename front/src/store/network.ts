import { Action, GetterTree, Module } from "vuex";
import { RootState } from "@/store/root";
import { Endpoint, EndpointCollection } from "@/api/endpoints";
import { Converter, createBackoff, Dictionary, Jsonified } from "@/utils";
import { ApiNode } from "@/model/network";
import { Mutation } from "@/store/types";
import { LoadBalancerNode } from "@/api/loadbalancer";
import { StaticClient } from "@/api/client/static";

export type NetworkingEndpoints = {
    v1_network_nodes: Endpoint<never, Jsonified<ApiNode>[]>,
    v1_status_health: Endpoint<never, Jsonified<any>>,
}

export const networkingEndpoints: NetworkingEndpoints = {
    v1_network_nodes: {
        template: '/api/v1/network/nodes',
        version: '1.0'
    },
    v1_status_health: {
        template: '/api/v1/status/health',
        version: '1.0',
    }
}

export const networkingClient = new StaticClient(networkingEndpoints)
export const nodeBackoff = createBackoff(5000);

export enum NetworkMutations {
    NodeJoined = "nodeJoined",
    NodeLeft = "nodeLeft",
    NodeSuspended = "nodeSuspended",
    NodeResumed = "nodeResumed",
    NodeListUpdated = "nodeListUpdated",
    NodeFailed = "nodeFailed",
    NodeRecovered = "nodeRecovered",
}

export enum NetworkActions {
    Update = "update",
    NodeFailed = "nodeFailed",
    NodeCheck = "nodeCheck",
}

export type NetworkNode = LoadBalancerNode<EndpointCollection>;

export interface NetworkNodeState extends NetworkNode {
    // backoff logic
    failures: number;
    failuresTotal: number;

    available: boolean;
    suspended: boolean;
}

export interface NetworkState {
    nodes: Dictionary<NetworkNodeState>,
}

export type NetworkMutationTree = {
    [NetworkMutations.NodeJoined]: Mutation<NetworkState, ApiNode>
    [NetworkMutations.NodeLeft]: Mutation<NetworkState, string>
    [NetworkMutations.NodeSuspended]: Mutation<NetworkState, string>
    [NetworkMutations.NodeResumed]: Mutation<NetworkState, string>
    [NetworkMutations.NodeFailed]: Mutation<NetworkState, string>
    [NetworkMutations.NodeRecovered]: Mutation<NetworkState, string>
    [NetworkMutations.NodeListUpdated]: Mutation<NetworkState, ApiNode[]>
}

export type NetworkActionTree = {
    [NetworkActions.Update]: Action<NetworkState, RootState>,
    [NetworkActions.NodeFailed]: Action<NetworkState, RootState>,
    [NetworkActions.NodeCheck]: Action<NetworkState, RootState>,
}

const emptyNetworkNode: NetworkNodeState = {
    id: "",
    url: "",
    endpoints: {},
    failures: 0,
    failuresTotal: 0,
    available: true,
    suspended: false,
}

const apiNodeConverter: Converter<ApiNode, NetworkNode> = {
    convert: (node: ApiNode): NetworkNode => ({
        ...node,
        endpoints: Object.fromEntries(node.endpoints.map(endpoint => [endpoint.name, endpoint])),
    })
}

const mutations: NetworkMutationTree = {
    [NetworkMutations.NodeJoined]: (state, node) => {
        const base = state.nodes[node.id] || emptyNetworkNode;

        state.nodes[node.id] = {
            ...base,
            ...(apiNodeConverter.convert(node)),
        }
    },
    [NetworkMutations.NodeLeft]: (state, id) => {
        delete state.nodes[id];
    },
    [NetworkMutations.NodeSuspended]: (state, id) => {
        // If node is undefined this mutation is no-op
        if (typeof state.nodes[id] === "undefined") {
            return;
        }

        state.nodes[id].suspended = true;
    },
    [NetworkMutations.NodeResumed]: (state, id) => {
        // If node is undefined this mutation is no-op
        if (typeof state.nodes[id] === "undefined") {
            return;
        }

        state.nodes[id].suspended = false;
    },
    [NetworkMutations.NodeFailed]: (state, id) => {
        // If node is undefined this mutation is no-op
        if (typeof state.nodes[id] === "undefined") {
            return;
        }

        const node = state.nodes[id];

        node.available = false;
        node.failures += 1;
        node.failuresTotal += 1;
    },
    [NetworkMutations.NodeRecovered]: (state, id) => {
        // If node is undefined this mutation is no-op
        if (typeof state.nodes[id] === "undefined") {
            return;
        }

        const node = state.nodes[id];

        node.available = true;
        node.failures = 0;
    },
    [NetworkMutations.NodeListUpdated]: (state, nodes) => {
        state.nodes = Object.fromEntries(
            nodes
                .map<NetworkNodeState>(node => {
                    const base = state.nodes[node.id] || emptyNetworkNode;
                    return {
                        ...base,
                        ...(apiNodeConverter.convert(node)),
                    }
                })
                .map(node => [ node.id, node ])
        )
    },
}

const actions: NetworkActionTree = {
    [NetworkActions.Update]: async ({ commit }) => {
        const response = await networkingClient.get("v1_network_nodes", { version: "^1.0" });

        commit(NetworkMutations.NodeListUpdated, response.data)
    },
    [NetworkActions.NodeFailed]: async ({ commit, state, dispatch }, id) => {
        const node = state.nodes[id];

        // If node was already removed from node list this is no-op
        if (typeof node === "undefined") {
            return;
        }

        commit(NetworkMutations.NodeFailed, id);
        nodeBackoff(node.failures, () => { dispatch(NetworkActions.NodeCheck, id); })
    },
    [NetworkActions.NodeCheck]: async ({ commit, state, dispatch }, id) => {
        const node = state.nodes[id];

        // If node was already removed from node list this is no-op
        if (typeof node === "undefined") {
            return;
        }

        try {
            const response = await networkingClient.get("v1_status_health", {
                version: "^1.0",
                base: node.url
            })

            commit(NetworkMutations.NodeRecovered, id);
        } catch {
            commit(NetworkMutations.NodeFailed, id);
            nodeBackoff(node.failures, () => { dispatch(NetworkActions.NodeCheck, id); })
        }
    }
}

const getters: GetterTree<NetworkState, RootState> = {
    available: state => Object.values(state.nodes).filter(node => node.available && !node.suspended)
}

export const network: Module<NetworkState, RootState> = {
    namespaced: true,
    getters,
    state: {
        nodes: {},
    },
    mutations,
    actions,
}

export default network;
