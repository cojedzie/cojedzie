import { Jsonified } from "@/utils";
import { Departure, Provider, Stop, Track } from "@/model";
import { Trip } from "@/model/trip";
import { Message } from "@/model/message";

export type Endpoint<TParams extends string, TResult = any> = {
    template: string,
    version: string,
};

export type EndpointCollection = { [name: string]: Endpoint<any> }

export type Endpoints = {
    v1_trip_details: Endpoint<"provider" | "id", Jsonified<Trip>>,
    v1_departure_list: Endpoint<"provider", Jsonified<Departure>[]>,
    v1_message_all: Endpoint<"provider", Jsonified<Message>[]>,
    v1_provider_details: Endpoint<"provider", Jsonified<Provider>>,
    v1_provider_list: Endpoint<never, Jsonified<Provider>[]>,
    v1_stop_list: Endpoint<"provider", Jsonified<Stop>[]>,
    v1_stop_groups: Endpoint<"provider", Jsonified<{ name: string, stops: Stop[] }>[]>,
    v1_stop_tracks: Endpoint<"provider" | "stop", Jsonified<{ order: number, track: Track }>[]>,
}

export type EndpointParams<TEndpoints extends EndpointCollection, TEndpoint extends keyof TEndpoints> =
    TEndpoints[TEndpoint] extends Endpoint<infer TParams, any> ? { [name in TParams]: string }: never;

export type EndpointResult<TEndpoints extends EndpointCollection, TEndpoint extends keyof TEndpoints> =
    TEndpoints[TEndpoint] extends Endpoint<string, infer TResult> ? TResult : never;

export const endpoints: Endpoints = {
    v1_trip_details: {
        template: '/api/v1/{provider}/trips/{id}',
        version: '1.0',
    },
    v1_departure_list: {
        template: '/api/v1/{provider}/departures',
        version: '1.0',
    },
    v1_message_all: {
        template: '/api/v1/{provider}/messages',
        version: '1.0',
    },
    v1_provider_details: {
        template: '/api/v1/providers/{provider}',
        version: '1.0'
    },
    v1_provider_list: {
        template: '/api/v1/providers',
        version: '1.0'
    },
    v1_stop_list: {
        template: '/api/v1/{provider}/stops',
        version: '1.0'
    },
    v1_stop_groups: {
        template: '/api/v1/{provider}/stops/groups',
        version: '1.0'
    },
    v1_stop_tracks: {
        template: '/api/v1/{provider}/stops/{stop}/tracks',
        version: '1.0'
    },
}

export default endpoints;
