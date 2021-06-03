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
