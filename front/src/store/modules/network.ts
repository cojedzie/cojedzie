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

import { Endpoint, EndpointCollection } from "@/api/endpoints";
import { Converter, createBackoff, Dictionary, Jsonified, supply } from "@/utils";
import { ApiNode, ApiNodeUpdate } from "@/model/network";
import { LoadBalancerNode } from "@/api/loadbalancer";
import { StaticClient } from "@/api/client/static";
import { query } from "@/api/utils";
import { AxiosResponse } from "axios";
// import EventSourcePolyfill from "eventsource";
import { NamespacedVuexModule, VuexActionHandler, VuexGetter, VuexMutationHandler } from "vuex";
import { createHttpClient } from "@/api/client/http";

const EventSource = typeof window !== "undefined" && window.EventSource;

export type NetworkingEndpoints = {
    v1_network_nodes: Endpoint<never, Jsonified<ApiNode>[]>;
    v1_status_health: Endpoint<never, Jsonified<unknown>>;
};

export const networkingEndpoints: NetworkingEndpoints = {
    v1_network_nodes: {
        template: "/api/v1/network/nodes",
        version: "1.0",
    },
    v1_status_health: {
        template: "/api/v1/status/health",
        version: "1.0",
    },
};

export const networkingClient = new StaticClient({
    endpoints: networkingEndpoints,
    http: createHttpClient({
        baseURL: window.CoJedzie.api.hub,
    }),
});

export const nodeBackoff = createBackoff(5000);
export const sseBackoff = createBackoff(1000);

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
    nodes: Dictionary<NetworkNodeState>;
}

export type NetworkMutationTree = {
    [NetworkMutations.NodeJoined]: VuexMutationHandler<NetworkState, ApiNode>;
    [NetworkMutations.NodeLeft]: VuexMutationHandler<NetworkState, string>;
    [NetworkMutations.NodeSuspended]: VuexMutationHandler<NetworkState, string>;
    [NetworkMutations.NodeResumed]: VuexMutationHandler<NetworkState, string>;
    [NetworkMutations.NodeFailed]: VuexMutationHandler<NetworkState, string>;
    [NetworkMutations.NodeRecovered]: VuexMutationHandler<NetworkState, string>;
    [NetworkMutations.NodeListUpdated]: VuexMutationHandler<NetworkState, ApiNode[]>;
};

export type NetworkActionTree = {
    [NetworkActions.Update]: VuexActionHandler<NetworkModule>;
    [NetworkActions.NodeFailed]: VuexActionHandler<NetworkModule, string>;
    [NetworkActions.NodeCheck]: VuexActionHandler<NetworkModule, string>;
};

export type NetworkGetterTree = {
    available: VuexGetter<NetworkModule, NetworkNode[]>;
};

export type NetworkModule = NamespacedVuexModule<
    NetworkState,
    NetworkMutationTree,
    NetworkActionTree,
    NetworkGetterTree
>;

const emptyNetworkNode: NetworkNodeState = {
    id: "",
    url: "",
    endpoints: {},
    failures: 0,
    failuresTotal: 0,
    available: true,
    suspended: false,
};

const apiNodeConverter: Converter<ApiNode, NetworkNode> = {
    convert: (node: ApiNode): NetworkNode => ({
        ...node,
        endpoints: Object.fromEntries(node.endpoints.map(endpoint => [endpoint.name, endpoint])),
    }),
};

const mutations: NetworkMutationTree = {
    [NetworkMutations.NodeJoined]: (state, node) => {
        const base = state.nodes[node.id] || emptyNetworkNode;

        state.nodes[node.id] = {
            ...base,
            ...apiNodeConverter.convert(node),
        };
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
                        ...apiNodeConverter.convert(node),
                    };
                })
                .map(node => [node.id, node])
        );
    },
};

const actions: NetworkActionTree = {
    async [NetworkActions.Update]({ commit }) {
        try {
            const response = await networkingClient.get("v1_network_nodes", {
                version: "^1.0",
            });

            if (Object.prototype.hasOwnProperty.call(response.headers, "Set-Cookie")) {
                document.cookie = response.headers["Set-Cookie"];
            }

            const hub = getMercureHub(response);

            if (hub && !listener.connected) {
                listener.initialize(hub, update => {
                    switch (update.event) {
                        case "node-joined":
                            commit(NetworkMutations.NodeJoined, update.node);
                            break;
                        case "node-left":
                            commit(NetworkMutations.NodeLeft, update.node.id);
                            break;
                        case "node-suspended":
                            commit(NetworkMutations.NodeSuspended, update.node.id);
                            break;
                        case "node-resumed":
                            commit(NetworkMutations.NodeResumed, update.node.id);
                            break;
                    }
                });
            }

            commit(NetworkMutations.NodeListUpdated, response.data as ApiNode[]);
        } catch (err) {
            console.log("Could not get network nodes");
        }
    },
    async [NetworkActions.NodeFailed]({ commit, state, dispatch }, id) {
        const node = state.nodes[id];

        // If node was already removed from node list this is no-op
        if (typeof node === "undefined") {
            return;
        }

        commit(NetworkMutations.NodeFailed, id);
        nodeBackoff(node.failures, () => {
            dispatch(NetworkActions.NodeCheck, id);
        });
    },
    async [NetworkActions.NodeCheck]({ commit, state, dispatch }, id) {
        const node = state.nodes[id];

        // If node was already removed from node list this is no-op
        if (typeof node === "undefined") {
            return;
        }

        try {
            await networkingClient.get("v1_status_health", {
                version: "^1.0",
                base: node.url,
            });

            commit(NetworkMutations.NodeRecovered, id);
        } catch {
            commit(NetworkMutations.NodeFailed, id);
            nodeBackoff(node.failures, () => {
                dispatch(NetworkActions.NodeCheck, id);
            });
        }
    },
};

const getters: NetworkGetterTree = {
    available: state => Object.values(state.nodes).filter(node => node.available && !node.suspended),
};

class NetworkNodeUpdateListener {
    sse: EventSource;

    url: string;
    handler: (ApiNodeUpdate) => void;

    get connected(): boolean {
        return !!this.sse;
    }

    initialize(url, handler: (ApiNodeUpdate) => void) {
        if (this.sse) {
            this.disconnect();
        }

        this.handler = handler;
        this.url = url;

        this.connect();
    }

    disconnect() {
        if (!this.connected) {
            return;
        }

        this.sse.close();
        this.sse = null;
    }

    connect() {
        // if already connected this is no-op
        if (this.sse) {
            return;
        }

        this.sse = new EventSource(this.url + "?" + query({ topic: "network/nodes" }));
        this.sse.addEventListener("message", this.handleUpdateEvent.bind(this));
        this.sse.addEventListener("error", this.handleConnectionError.bind(this));
    }

    handleUpdateEvent(event: MessageEvent) {
        const update = JSON.parse(event.data) as ApiNodeUpdate;

        this.handler?.(update);
    }

    handleConnectionError(_: Event) {
        if (this.sse.readyState !== 2) {
            return;
        }

        this.sse.close();
        this.sse = null;

        sseBackoff(1, () => this.connect());
    }
}

const listener: NetworkNodeUpdateListener = new NetworkNodeUpdateListener();

const getMercureHub = (response: AxiosResponse): string | undefined => {
    const link = response.headers["link"];

    if (typeof link === "undefined") {
        return undefined;
    }

    return link.match(/<([^>]+)>;\s+rel=(?:mercure|"[^"]*mercure[^"]*")/)[1];
};

export const network: NetworkModule = {
    namespaced: true,
    getters,
    state: supply({
        nodes: {},
    }),
    mutations,
    actions,
};

export default network;
