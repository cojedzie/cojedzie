import { Stop } from "./stop";
import { Line } from "./line";
import { Moment } from "moment";
import { Identity } from "./identity";

export interface Departure {
    key: string;
    display: string;
    estimated: Moment;
    scheduled?: Moment;
    stop: Stop;
    line: Line;
    delay: number;

    vehicle?: Vehicle;
    trip?: Identity;
}

export interface Vehicle {
    id: string;

    // todo: ???
}
