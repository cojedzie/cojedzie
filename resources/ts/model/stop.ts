import { Line } from "./line";

export interface Stop {
    id: any;
    name: string;
    description?: string;
    location?: {
        lat: number,
        lng: number,
    };
    onDemand?: boolean;
    variant?: string;
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
