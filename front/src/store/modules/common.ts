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

import { FetchingState, supply } from "@/utils";
import moment, { Moment } from "moment";
import { VuexMutationHandler } from "vuex";

export interface CommonState {
    state: FetchingState,
    lastUpdate: Moment,
    error: string
}

export const state: CommonState = {
    state: "not-initialized",
    error: "",
    lastUpdate: moment()
}

export type CommonMutationTree = {
    fetching: VuexMutationHandler<CommonState>,
    error: VuexMutationHandler<CommonState, string>,
}

export const mutations: CommonMutationTree = {
    fetching: (state) => state.state = 'fetching',
    error:    (state, error) => {
        state.state = 'error';
        state.error = error;
    }
};

export default {
    state: supply(state),
    mutations
};
