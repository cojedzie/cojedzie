import { Moment } from "moment";
import { Location } from "./common";

export interface Provider {
    id: string;
    name: string;
    shortName: string;
    attribution?: string;
    lastUpdate?: Moment;
    location: Location;
}
