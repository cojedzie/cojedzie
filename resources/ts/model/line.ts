export type LineType = "tram" | "bus" | "trolleybus" | "train" | "other";

export interface Line {
    id: any;
    symbol: string;
    type: LineType;
    night: boolean;
    fast: boolean;
}

export interface Track {
    id: string;
    description: string;
    line: Line;
}