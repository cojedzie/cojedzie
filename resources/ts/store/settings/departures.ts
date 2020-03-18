import { ActionContext, Module } from "vuex";
import { RootState } from "../root";

export type DeparturesSettingsState = {
    autorefresh: boolean;
    autorefreshInterval?: number;
    displayedEntriesCount?: number;
}

const departureSettings: Module<DeparturesSettingsState, RootState> = {
    namespaced: true,
    state: {
        autorefresh: true,
        autorefreshInterval: 10,
        displayedEntriesCount: 10
    },
    mutations: {
        update(state: DeparturesSettingsState, patch: Partial<DeparturesSettingsState>) {
            Object.assign(state, patch);
        }
    }
};

export default departureSettings;
