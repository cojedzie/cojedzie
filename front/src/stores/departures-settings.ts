/*
 * Copyright (C) 2026 Kacper Donat
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

import { defineStore } from "pinia";
import { reactive, toRefs } from "vue";
import { storages } from "@/stores/persistence/storages";

export type DeparturesSettingsState = {
    autorefresh: boolean;
    autorefreshInterval?: number;
    displayedEntriesCount?: number;
    relativeTimes: boolean;
    relativeTimesForScheduled: boolean;
    relativeTimesLimit: number;
    relativeTimesLimitEnabled: boolean;
};

export const useDeparturesSettingsStore = defineStore(
    "departures-settings",
    () => {
        const settings = reactive<DeparturesSettingsState>({
            autorefresh: true,
            autorefreshInterval: 10,
            displayedEntriesCount: 10,
            relativeTimes: false,
            relativeTimesForScheduled: true,
            relativeTimesLimit: 40,
            relativeTimesLimitEnabled: true,
        });

        function update(patch: Partial<DeparturesSettingsState>) {
            Object.assign(settings, patch);
        }

        return { ...toRefs(settings), update };
    },
    {
        persist: { key: "departures-settings", storage: storages.local },
    },
);
