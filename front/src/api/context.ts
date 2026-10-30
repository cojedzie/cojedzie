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

import { EndpointCollection } from "@/api/endpoints";
import type { LoadBalancerNode } from "@/api/loadbalancer";

export interface NetworkAccess {
    available(): LoadBalancerNode<EndpointCollection>[];
    update(): Promise<void>;
    nodeFailed(id: string): Promise<void>;
}

export interface ClientContext {
    network: NetworkAccess;
    provider(): string | undefined;
}

const unbound: ClientContext = {
    network: {
        available: () => [],
        update: async () => {},
        nodeFailed: async () => {},
    },
    provider: () => undefined,
};

let current: ClientContext = unbound;

export function bindClientContext(context: ClientContext) {
    current = context;

    // the balancer's first update fires before binding, so refresh as soon as the context is available
    context.network.update().catch(error => console.error("Failed to update API nodes", error));
}

export function resetClientContext() {
    current = unbound;
}

export function clientContext(): ClientContext {
    return current;
}
