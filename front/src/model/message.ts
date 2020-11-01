import { Moment } from "moment";

export type MessageType = "info" | "breakdown" | "unknown";
export interface Message {
    type: MessageType;
    message: string;

    validFrom: Moment;
    validTo: Moment;
}