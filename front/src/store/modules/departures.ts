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

import { Departure, Line } from "../../model";
import moment from 'moment'
import common, { CommonMutations, CommonMutationTree, CommonState } from './common'
import { resolve } from "@/utils";
import { NamespacedVuexModule, VuexActionHandler, VuexMutationHandler } from "vuex";

export enum DeparturesActions {
    Update = "update",
}

export enum DeparturesMutations {
    ListReceived = "listReceived",
}

export interface DeparturesState extends CommonState {
    departures: Departure[],
}

export type DeparturesMutationTree = {
    [DeparturesMutations.ListReceived]: VuexMutationHandler<DeparturesState, Departure[]>,
}

export type DeparturesActionTree = {
    [DeparturesActions.Update]: VuexActionHandler<DeparturesModule>,
}

const mutations: DeparturesMutationTree = {
    [DeparturesMutations.ListReceived]: (state, departures) => {
        state.departures = departures;
        state.lastUpdate = moment();
        state.state      = 'ready';
    }
}

const actions: DeparturesActionTree = {
    async [DeparturesActions.Update]({ commit }) {
        const count = this.state['departures-settings'].displayedEntriesCount;
        const stops = this.state.stops;

        if (stops.length == 0) {
            return;
        }

        commit(CommonMutations.Fetching);

        try {
            const response = await this.$api.get('v1_departure_list', {
                version: "^1.0",
                query: {
                    stop: stops.map(stop => stop.id),
                    limit: count || 8,
                }
            });

            const departures = response.data;

            commit(
                DeparturesMutations.ListReceived,
                departures.map((departure): Departure => ({
                    ...departure,
                    line: departure.line as Line,
                    scheduled: moment.parseZone(departure.scheduled).local(),
                    estimated: departure.estimated && moment.parseZone(departure.estimated).local(),
                }))
            );
        } catch (response) {
            commit(CommonMutations.Error, JSON.stringify(response));
        }
    }
}

export type DeparturesModule = NamespacedVuexModule<
    DeparturesState & CommonState,
    DeparturesMutationTree & CommonMutationTree,
    DeparturesActionTree
>

export const departures: DeparturesModule = {
    namespaced: true,
    state: () => ({
        departures: [ ],
        ...resolve(common.state)
    }),
    mutations: {
        ...mutations,
        ...common.mutations
    },
    actions,
};

export default departures;
