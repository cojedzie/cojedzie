import { ActionContext, Module, Store } from "vuex";
import { RootState } from "./root";
import { Message, MessageType } from "../model/message";

import urls from "../urls";
import { FetchingState, Jsonified } from "../utils";
import * as moment from 'moment';
import { Moment } from "moment";

export interface MessagesState {
    messages: Message[],
    state: FetchingState,
    lastUpdate: Moment
}

export const messages: Module<MessagesState, RootState> = {
    namespaced: true,
    state: {
        messages: [],
        state: "not-initialized",
        lastUpdate: moment()
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
        fetching: (state: MessagesState) => state.state = 'fetching',
        error:    (state: MessagesState, error) => state.state = 'error',
    },
    actions: {
        async update({ commit }: ActionContext<MessagesState, RootState>) {
            commit('fetching');

            const response = await fetch(urls.prepare(urls.messages));

            if (!response.ok) {
                commit('error', await response.json());
                return;
            }

            const messages = await response.json() as Jsonified<Message>[];
            commit('update', messages.map(m => {
                const message = m as Message;

                message.validFrom = moment(m.validFrom);
                message.validTo   = moment(m.validTo);

                return message;
            }));
        }
    }
};
