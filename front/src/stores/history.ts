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
import { computed, reactive, ref } from "vue";
import moment, { type Moment } from "moment";
import type { Stop } from "@/model";
import { storages } from "@/stores/persistence/storages";
import type { Jsonified } from "@/utils";

export interface HistoryEntry {
    stop: Stop;
    date: Moment;
}

export interface HistorySettings {
    maxEntries: number;
}

type SerializedEntry = Jsonified<HistoryEntry>;

const serialize = (entry: HistoryEntry): SerializedEntry => ({ ...entry, date: entry.date.toISOString() });
const deserialize = (entry: SerializedEntry): HistoryEntry => ({ ...entry, date: moment(entry.date) });

export const useHistoryStore = defineStore(
    "history",
    () => {
        const entries = ref<SerializedEntry[]>([]);
        const settings = reactive<HistorySettings>({ maxEntries: 10 });

        const all = computed(() => entries.value.slice(0, settings.maxEntries).map(deserialize));

        function latest(n: number): HistoryEntry[] {
            return entries.value.slice(0, n).map(deserialize);
        }

        function push(entry: HistoryEntry) {
            entries.value = [
                serialize(entry),
                ...entries.value.filter(current => current.stop.id !== entry.stop.id),
            ].slice(0, settings.maxEntries);
        }

        function clear() {
            entries.value = [];
        }

        function saveSettings(patch: Partial<HistorySettings>) {
            Object.assign(settings, patch);
        }

        return { entries, settings, all, latest, push, clear, saveSettings };
    },
    {
        persist: {
            key: "history",
            storage: storages.local,
            pick: state => ({ entries: state.entries, settings: state.settings }),
        },
    },
);
