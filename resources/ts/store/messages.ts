import { ActionContext, Module } from "vuex";
import { RootState } from "./root";
import { Message, MessageType } from "../model/message";
import common, { CommonState } from "./common";
import urls from "../urls";
import { Jsonified } from "../utils";
import * as moment from 'moment';

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

            const response = await fetch(urls.prepare(urls.messages));

            if (!response.ok) {
                const error = await response.json() as Error;
                commit('error', error.message);
                return;
            }

            const messages = await response.json() as Jsonified<Message>[];
            commit('update', messages.map(m => ({
                ...m,
                validFrom: moment(m.validFrom),
                validTo:   moment(m.validTo),
            })));
        }
    }
};

export default messages;
