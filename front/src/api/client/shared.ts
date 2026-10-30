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

import { LoadBalancedClient } from "@/api/client/balanced";
import { createHttpClient } from "@/api/client/http";
import { clientContext } from "@/api/context";
import { LoadBalancerImplementation } from "@/api/loadbalancer";
import { endpoints } from "@/api/endpoints";

export const client = new LoadBalancedClient({
    http: createHttpClient({
        baseURL: window.CoJedzie?.api?.base || `${window.location.protocol}//${window.location.host}`,
    }),
    balancer: new LoadBalancerImplementation(endpoints),
    bound: () => ({ provider: clientContext().provider() }),
});
