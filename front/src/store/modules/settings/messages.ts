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

import { supply } from "@/utils";
import { NamespacedVuexModule, VuexMutationHandler } from "vuex";

export type MessagesSettingsState = {
    autorefresh: boolean;
    autorefreshInterval?: number;
    displayedEntriesCount?: number;
}

export enum MessagesSettingsMutations {
    Update = "update",
}

export type MessagesSettingsMutationTree = {
    [MessagesSettingsMutations.Update]: VuexMutationHandler<MessagesSettingsState, Partial<MessagesSettingsState>>
}

export type MessagesSettingsModule = NamespacedVuexModule<MessagesSettingsState, MessagesSettingsMutationTree>

const messagesSettings: MessagesSettingsModule = {
    namespaced: true,
    state: supply({
        autorefresh: true,
        autorefreshInterval: 60,
        displayedEntriesCount: 2
    }),
    mutations: {
        [MessagesSettingsMutations.Update](state, patch) {
            Object.assign(state, patch);
        }
    }
};

export default messagesSettings;
