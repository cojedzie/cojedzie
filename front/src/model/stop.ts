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

import { Line, LineType } from "./line";
import { Location } from "./common";
import { uniq } from "lodash";

export interface Stop {
    id: string;
    name: string;
    description?: string;
    location?: Location;
    onDemand?: boolean;
    variant?: string;
}

export type HasDestinations = {
    destinations?: Destination[];
}

export type StopWithDestinations = Stop & HasDestinations;

export type Destination = {
    stop: Stop;
    lines: Line[]
}

export type StopGroup = Stop[];

export type StopGroups = {
    [name: string]: StopGroup;
}

export function getStopTypes(stop: StopWithDestinations) {
    return uniq(stop.destinations.flatMap(destination => destination.lines.map(line => line.type)))
}

export function getStopType(stop: StopWithDestinations): LineType {
    return getStopTypes(stop)[0] || "unknown";
}
