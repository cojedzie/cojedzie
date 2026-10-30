/*
 * Copyright (C) 2026 Kacper Donat
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

import { defineStore } from "pinia";
import { computed, ref } from "vue";
import type { AxiosResponse } from "axios";
import type { Endpoint, EndpointCollection } from "@/api/endpoints";
import type { LoadBalancerNode } from "@/api/loadbalancer";
import { StaticClient } from "@/api/client/static";
import { createHttpClient } from "@/api/client/http";
import { query } from "@/api/utils";
import type { ApiNode, ApiNodeUpdate } from "@/model/network";
import { createBackoff, type Converter, type Dictionary, type Jsonified } from "@/utils";

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

export type NetworkNode = LoadBalancerNode<EndpointCollection>;

export interface NetworkNodeState extends NetworkNode {
    // backoff logic
    failures: number;
    failuresTotal: number;

    available: boolean;
    suspended: boolean;
}

const nodeBackoff = createBackoff(5000);
const sseBackoff = createBackoff(1000);

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

const getMercureHub = (response: AxiosResponse): string | undefined => {
    const link = response.headers["link"];

    if (typeof link === "undefined") {
        return undefined;
    }

    return link.match(/<([^>]+)>;\s+rel=(?:mercure|"[^"]*mercure[^"]*")/)[1];
};

class NetworkNodeUpdateListener {
    sse: EventSource | null = null;

    url: string;
    handler: (update: ApiNodeUpdate) => void;

    get connected(): boolean {
        return !!this.sse;
    }

    initialize(url: string, handler: (update: ApiNodeUpdate) => void) {
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

export const useNetworkStore = defineStore("network", () => {
    const nodes = ref<Dictionary<NetworkNodeState>>({});

    const listener = new NetworkNodeUpdateListener();

    // The hub address is read lazily, so merely importing the store does not need `window.CoJedzie`.
    let client: StaticClient<NetworkingEndpoints> | undefined;
    const networkingClient = () =>
        (client ??= new StaticClient({
            endpoints: networkingEndpoints,
            http: createHttpClient({ baseURL: window.CoJedzie.api.hub }),
        }));

    const available = computed<NetworkNode[]>(() =>
        Object.values(nodes.value).filter(node => node.available && !node.suspended),
    );

    function nodeJoined(node: ApiNode) {
        const base = nodes.value[node.id] || emptyNetworkNode;

        nodes.value[node.id] = { ...base, ...apiNodeConverter.convert(node) };
    }

    function nodeLeft(id: string) {
        delete nodes.value[id];
    }

    // Mutations on unknown nodes are no-ops.
    function nodeSuspended(id: string) {
        if (nodes.value[id]) {
            nodes.value[id].suspended = true;
        }
    }

    function nodeResumed(id: string) {
        if (nodes.value[id]) {
            nodes.value[id].suspended = false;
        }
    }

    function markFailed(id: string) {
        const node = nodes.value[id];

        if (node) {
            node.available = false;
            node.failures += 1;
            node.failuresTotal += 1;
        }
    }

    function nodeRecovered(id: string) {
        const node = nodes.value[id];

        if (node) {
            node.available = true;
            node.failures = 0;
        }
    }

    function nodeListUpdated(list: ApiNode[]) {
        nodes.value = Object.fromEntries(
            list
                .map<NetworkNodeState>(node => ({
                    ...(nodes.value[node.id] || emptyNetworkNode),
                    ...apiNodeConverter.convert(node),
                }))
                .map(node => [node.id, node]),
        );
    }

    async function update() {
        try {
            const response = await networkingClient().get("v1_network_nodes", {
                version: "^1.0",
            });

            if (Object.prototype.hasOwnProperty.call(response.headers, "Set-Cookie")) {
                document.cookie = response.headers["Set-Cookie"];
            }

            const hub = getMercureHub(response);

            if (hub && !listener.connected) {
                listener.initialize(hub, event => {
                    switch (event.event) {
                        case "node-joined":
                            nodeJoined(event.node);
                            break;
                        case "node-left":
                            nodeLeft(event.node.id);
                            break;
                        case "node-suspended":
                            nodeSuspended(event.node.id);
                            break;
                        case "node-resumed":
                            nodeResumed(event.node.id);
                            break;
                    }
                });
            }

            nodeListUpdated(response.data as ApiNode[]);
        } catch (err) {
            console.log("Could not get network nodes");
        }
    }

    async function nodeFailed(id: string) {
        const node = nodes.value[id];

        // If node was already removed from node list this is no-op
        if (typeof node === "undefined") {
            return;
        }

        markFailed(id);
        nodeBackoff(node.failures, () => {
            nodeCheck(id);
        });
    }

    async function nodeCheck(id: string) {
        const node = nodes.value[id];

        // If node was already removed from node list this is no-op
        if (typeof node === "undefined") {
            return;
        }

        try {
            await networkingClient().get("v1_status_health", {
                version: "^1.0",
                base: node.url,
            });

            nodeRecovered(id);
        } catch {
            markFailed(id);
            nodeBackoff(node.failures, () => {
                nodeCheck(id);
            });
        }
    }

    return { nodes, available, update, nodeFailed, nodeCheck };
});
