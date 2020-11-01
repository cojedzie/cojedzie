import { Stop } from "./stop";
import { Moment } from "moment";

export type ScheduledStop = {
    stop: Stop,
    departure: Moment,
    arrival: Moment,
    order: number,
}

export type Trip = {
    id: string,
    schedule: ScheduledStop[],
    variant: string,
    description: string,
}
