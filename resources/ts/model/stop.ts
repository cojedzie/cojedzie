import { Line } from "./line";
import { Location } from "./common";

export interface Stop {
    id: any;
    name: string;
    description?: string;
    location?: Location;
    onDemand?: boolean;
    variant?: string;
}

export interface StopWithDestinations extends Stop{
    destinations?: Destination[];
}

export type Destination = {
    stop: Stop;
    lines: Line[]
}

export type StopGroup = Stop[];

export type StopGroups = {
    [name: string]: StopGroup;
}
