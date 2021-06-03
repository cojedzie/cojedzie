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
import { RootState } from "../root";

export type DeparturesSettingsState = {
    autorefresh: boolean;
    autorefreshInterval?: number;
    displayedEntriesCount?: number;
    relativeTimes: boolean,
}

const departureSettings: Module<DeparturesSettingsState, RootState> = {
    namespaced: true,
    state: {
        autorefresh: true,
        relativeTimes: false,
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
