import { ActionContext, Module } from "vuex";
import { RootState } from "./root";
import { Message, MessageType } from "@/model/message";
import common, { CommonState } from "./common";
import * as moment from 'moment';
import api from "@/api";

export interface MessagesState extends CommonState {
    messages: Message[]
}

export const messages: Module<MessagesState, RootState> = {
    namespaced: true,
    state: {
        messages: [],
        ...common.state,
    },
    getters: {
        count: state => state.messages.length,
        counts: (state: MessagesState): { [x in MessageType]: number } => ({
            info:      state.messages.filter(m => m.type === 'info').length,
            unknown:   state.messages.filter(m => m.type === 'unknown').length,
            breakdown: state.messages.filter(m => m.type === 'breakdown').length,
        })
    },
    mutations: {
        update: (state: MessagesState, messages: Message[]) => {
            state.messages   = messages;
            state.lastUpdate = moment();
            state.state      = 'ready';
        },
        ...common.mutations
    },
    actions: {
        async update({ commit }: ActionContext<MessagesState, RootState>) {
            commit('fetching');

            try {
                const response = await api.get("v1_message_all", { version: "^1.0" });
                const messages = response.data;

                commit('update', messages.map(message => ({
                    ...message,
                    validFrom: moment(message.validFrom),
                    validTo:   moment(message.validTo),
                })));
            } catch (response) {
                const error = response.data as Error;
                commit('error', error.message);
            }
        }
    }
};

export default messages;
