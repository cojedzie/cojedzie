import { Stop } from "./stop";
import { Line } from "./line";
import { Moment } from "moment";

export interface Departure {
    id: string;
    display: string;
    estimated: Moment;
    scheduled?: Moment;
    stop: Stop;
    line: Line;
    delay: number;

    vehicle?: Vehicle;
}

export interface Vehicle {
    id: string;

    // todo: ???
}
