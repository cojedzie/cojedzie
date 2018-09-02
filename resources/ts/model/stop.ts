export interface Stop {
    id: any;
    name: string;
    description?: string;
    location?: [ number, number ];
    onDemand?: boolean;
    variant?: string;
}

export type StopGroup = Stop[];

export type StopGroups = {
    [name: string]: StopGroup;
}