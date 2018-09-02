export type LineType = "tram" | "bus" | "trolleybus" | "train" | "other";

export interface Line {
    id:       any;
    symbol:   string;
    variant?: string;
    type:     LineType;
}