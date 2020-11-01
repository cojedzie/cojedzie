import { FetchingState } from "@/utils";
import * as moment from "moment";
import { Moment } from "moment";
import { MutationTree } from "vuex";

export interface CommonState {
    state: FetchingState,
    lastUpdate: Moment,
    error: string
}

export const state: CommonState = {
    state: "not-initialized",
    error: "",
    lastUpdate: moment()
};

export const mutations: MutationTree<CommonState> = {
    fetching: (state) => state.state = 'fetching',
    error:    (state, error) => {
        state.state = 'error';
        state.error = error;
    }
};

export default { state, mutations };
