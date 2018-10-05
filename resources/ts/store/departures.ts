import { Module } from "vuex";
import { RootState } from "./root";
import { Departure, Stop } from "../model";
import * as moment from 'moment'
import common, { CommonState } from './common'
import urls from "../urls";
import { Jsonified } from "../utils";

export interface DeparturesState extends CommonState {
    departures: Departure[],
}

export interface ObtainPayload {
    stops: Stop[]
}

export const departures: Module<DeparturesState, RootState> = {
    namespaced: true,
    state: {
        departures: [ ],
        ...common.state
    },
    mutations: {
        update: (state, departures) => {
            state.departures = departures;
            state.lastUpdate = moment();
            state.state      = 'ready';
        },
        ...common.mutations
    },
    actions: {
        async update({ commit }, { stops }: ObtainPayload) {
            commit('fetching');
            const response = await fetch(urls.prepare(urls.departures, {
                stop: stops.map(stop => stop.id),
            }));

            if (!response.ok) {
                const error = await response.json() as Error;
                commit('error', error.message);

                return;
            }

            const departures = await response.json() as Jsonified<Departure>[];
            commit('update', departures.map(departure => {
                departure.scheduled = moment.parseZone(departure.scheduled);
                departure.estimated = moment.parseZone(departure.estimated);

                return departure as Departure;
            }));
        }
    }
};

export default departures;