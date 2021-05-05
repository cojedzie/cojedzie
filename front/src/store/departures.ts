import { Module } from "vuex";
import { RootState } from "./root";
import { Departure, Line } from "../model";
import * as moment from 'moment'
import common, { CommonState } from './common'
import api from "@/api";

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

            try {
                const response = await api.get('v1_departure_list', {
                    version: "^1.0",
                    query: {
                        stop: stops.map(stop => stop.id),
                        limit: count || 8,
                    }
                });

                const departures = response.data;

                commit('update', departures.map((departure): Departure => ({
                    ...departure,
                    line: departure.line as Line,
                    scheduled: moment.parseZone(departure.scheduled),
                    estimated: departure.estimated && moment.parseZone(departure.estimated),
                })));
            } catch (response) {
                const error = response.data as Error;
                commit('error', error.message);
            }
        }
    }
};

export default departures;
