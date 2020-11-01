import { Module } from "vuex";
import { RootState } from "./root";
import { Departure, Line, Stop } from "../model";
import * as moment from 'moment'
import common, { CommonState } from './common'
import urls from "../urls";
import { Jsonified } from "../utils";

export interface DeparturesState extends CommonState {
    departures: Departure[],
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
        async update({ commit }) {
            const count = this.state['departures-settings'].displayedEntriesCount;
            const stops = this.state.stops;

            commit('fetching');

            const response = await fetch(urls.prepare(urls.departures, {
                stop: stops.map(stop => stop.id),
                limit: count || 8,
            }));

            if (!response.ok) {
                const error = await response.json() as Error;
                commit('error', error.message);

                return;
            }

            const departures = await response.json() as Jsonified<Departure>[];
            commit('update', departures.map((departure): Departure => ({
                ...departure,
                line: departure.line as Line,
                scheduled: moment.parseZone(departure.scheduled),
                estimated: departure.estimated && moment.parseZone(departure.estimated),
            })));
        }
    }
};

export default departures;
