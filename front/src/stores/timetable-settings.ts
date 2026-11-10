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

export type TimetableSettingsState = {
    /** List or hour rows, on every timetable; null until picked, when each page shows its own default */
    view: "list" | "hours" | null;
    /** Per line or combined, on a whole stop's timetable (one line's is per line anyway) */
    grouping: "line" | "combined";
};

export const useTimetableSettingsStore = defineStore(
    "timetable-settings",
    () => {
        const settings = reactive<TimetableSettingsState>({
            view: null,
            grouping: "line",
        });

        function update(patch: Partial<TimetableSettingsState>) {
            Object.assign(settings, patch);
        }

        return { ...toRefs(settings), update };
    },
    {
        persist: { key: "timetable-settings", storage: storages.local },
    },
);
