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
}

export type StopGroup = Stop[];

export type StopGroups = {
    [name: string]: StopGroup;
}
