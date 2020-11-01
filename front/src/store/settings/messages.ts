import { Module } from "vuex";
import { RootState } from "../root";

export type MessagesSettingsState = {
    autorefresh: boolean;
    autorefreshInterval?: number;
    displayedEntriesCount?: number;
}

const messagesSettings: Module<MessagesSettingsState, RootState> = {
    namespaced: true,
    state: {
        autorefresh: true,
        autorefreshInterval: 60,
        displayedEntriesCount: 2
    },
    mutations: {
        update(state: MessagesSettingsState, patch: Partial<MessagesSettingsState>) {
            Object.assign(state, patch);
        }
    }
};

export default messagesSettings;
