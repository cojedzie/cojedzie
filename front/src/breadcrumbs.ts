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

import type { Component } from "vue";
import type { LocationQuery, RouteLocation, RouteLocationNamedRaw, Router } from "vue-router";
import type { PredefinedIcon } from "@/icons";
import type { Line, Stop } from "@/model";

/** What the pages of a section loaded, for their crumbs to name; a label checks that it belongs to its route */
export interface TrailData {
    line?: Line | null;
    stop?: Stop | null;
}

/** The part of a route a crumb is built from */
export type TrailRoute = Pick<RouteLocation, "name" | "params" | "query">;

/** A route's crumb, declared in its meta */
export interface CrumbDeclaration {
    /** the crumb's text; without the data it needs, what the crumb is ("Linia") */
    label: (route: TrailRoute, data: TrailData) => string;
    /** what to show instead of the text once the data is there, e.g. a stop with its code in a badge */
    content?: (route: TrailRoute, data: TrailData) => CrumbContent | null;
    /** shown instead of the text when the trail runs out of space, e.g. the section's icon */
    icon?: PredefinedIcon;
    /** the route of the previous crumb, which needn't be the route's parent in the router */
    parent?: string;
    /** the query keys the crumb's link keeps from the current route */
    query?: string[];
}

declare module "vue-router" {
    interface RouteMeta {
        breadcrumb?: CrumbDeclaration;
    }
}

/** A component and its props, rendered as a crumb */
export interface CrumbContent {
    component: Component;
    props: Record<string, unknown>;
}

export interface Crumb {
    /** the crumb's text, and its accessible name when it shows `content` */
    label: string;
    content?: CrumbContent;
    icon?: PredefinedIcon;
    /** the content the crumb declares isn't loaded yet, the label only says what the crumb is */
    pending: boolean;
    to: RouteLocationNamedRaw;
    /** the page the rider is on, the last crumb */
    current: boolean;
}

/**
 * The crumbs of a route, root first: the route's own and those of its declared parents. Each links to its route with
 * the current route's params it takes and the query keys it keeps. A route without a crumb has no trail.
 */
export function buildTrail(route: TrailRoute, router: Router, data: TrailData): Crumb[] {
    const records = router.getRoutes();
    const crumbs: Crumb[] = [];

    for (let name = route.name; name;) {
        const record = records.find(record => record.name === name);
        const declaration = record?.meta.breadcrumb;

        if (!declaration) {
            break;
        }

        const params = Object.fromEntries(
            [...record.path.matchAll(/:(\w+)/g)].map(([, param]) => [param, route.params[param]]),
        );

        const content = declaration.content?.(route, data) ?? undefined;

        crumbs.unshift({
            label: declaration.label(route, data),
            content,
            icon: declaration.icon,
            pending: !!declaration.content && !content,
            to: { name, params, query: keep(route.query, declaration.query ?? []) },
            current: crumbs.length === 0,
        });

        name = declaration.parent;
    }

    return crumbs;
}

const keep = (query: LocationQuery, keys: string[]): LocationQuery =>
    Object.fromEntries(keys.filter(key => key in query).map(key => [key, query[key]]));
