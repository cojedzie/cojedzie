/*
 * Copyright (C) 2021 Kacper Donat
 *
 * @author Kacper Donat <kacper@kadet.net>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

import { Module } from "vuex";
import { RootState } from "../root";
import { Departure, Line } from "../../model";
import moment from 'moment'
import common, { CommonState } from './common'
import { resolve } from "@/utils";

export interface DeparturesState extends CommonState {
    departures: Departure[],
}

export const departures: Module<DeparturesState, RootState> = {
    namespaced: true,
    state: () => ({
        departures: [ ],
        ...resolve(common.state)
    }),
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
                const response = await this.$api.get('v1_departure_list', {
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
                commit('error', JSON.stringify(response));
            }
        }
    }
};

export default departures;
