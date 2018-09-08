export type LineType = "tram" | "bus" | "trolleybus" | "train" | "other";

export interface Line {
    id: any;
    symbol: string;
    type: LineType;
    night: boolean;
    fast: boolean;
}