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

import { Message, MessageType } from "@/model/message";
import common, { CommonMutations, CommonMutationTree, CommonState } from "./common";
import moment from 'moment';
import { resolve, supply } from "@/utils";
import { NamespacedVuexModule, VuexActionHandler, VuexMutationHandler, VuexGetter } from "vuex";

export enum MessagesActions {
    Update = "update",
}

export enum MessagesMutations {
    ListReceived = "listReceived",
}

export interface MessagesState extends CommonState {
    messages: Message[]
}

export type MessagesMutationTree = {
    [MessagesMutations.ListReceived]: VuexMutationHandler<MessagesState, Message[]>,
}

export type MessagesActionTree = {
    [MessagesActions.Update]: VuexActionHandler<MessagesModule>,
}

export type MessagesGettersTree = {
    count: VuexGetter<MessagesModule, number>,
    counts: VuexGetter<MessagesModule, Record<MessageType, number>>,
}

const mutations: MessagesMutationTree = {
    [MessagesMutations.ListReceived]: (state: MessagesState, messages: Message[]) => {
        state.messages   = messages;
        state.lastUpdate = moment();
        state.state      = 'ready';
    }
}

const actions: MessagesActionTree = {
    async [MessagesActions.Update]({ commit }) {
        commit(CommonMutations.Fetching);

        try {
            const response = await this.$api.get("v1_message_all", { version: "^1.0" });
            const messages = response.data;

            commit(
                MessagesMutations.ListReceived,
                messages.map(message => ({
                    ...message,
                    validFrom: moment(message.validFrom),
                    validTo:   moment(message.validTo),
                })) as Message[]
            );
        } catch (response) {
            commit(CommonMutations.Error, JSON.stringify(response));
        }
    }
}

export type MessagesModule = NamespacedVuexModule<
    MessagesState & CommonState,
    MessagesMutationTree & CommonMutationTree,
    MessagesActionTree,
    MessagesGettersTree
>;

export const messages: MessagesModule = {
    namespaced: true,
    state: supply({
        messages: [],
        ...resolve(common.state),
    }),
    getters: {
        count: state => state.messages.length,
        counts: state => ({
            info:      state.messages.filter(m => m.type === 'info').length,
            unknown:   state.messages.filter(m => m.type === 'unknown').length,
            breakdown: state.messages.filter(m => m.type === 'breakdown').length,
        })
    },
    mutations: {
        ...mutations,
        ...common.mutations,
    },
    actions,
};

export default messages;
