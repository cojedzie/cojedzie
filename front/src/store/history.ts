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

import { Stop } from "@/model";
import { Module } from "vuex";
import { RootState } from "./root";
import * as moment from "moment";
import { Moment } from "moment";
import { Jsonified } from "@/utils";

export interface HistoryEntry {
    stop: Stop,
    date: Moment,
}

export interface HistorySettings {
    maxEntries: number,
}

export interface HistoryState {
    entries: Jsonified<HistoryEntry>[],
    settings: HistorySettings,
}

export function serializeHistoryEntry(entry: HistoryEntry): Jsonified<HistoryEntry> {
    return {
        ...entry,
        date: entry.date.toISOString(),
    }
}

export function deserializeHistoryEntry(serialized: Jsonified<HistoryEntry>): HistoryEntry {
    return {
        ...serialized,
        date: moment(serialized.date),
    }
}

export const history: Module<HistoryState, RootState> = {
    namespaced: true,
    state: {
        entries: [],
        settings: {
            maxEntries: 10,
        }
    },
    mutations: {
        clear(state: HistoryState) {
            state.entries = [];
        },
        push(state: HistoryState, entry: HistoryEntry) {
            state.entries = state.entries.filter(cur => cur.stop.id != entry.stop.id);
            state.entries.unshift(serializeHistoryEntry(entry));

            if (state.entries.length > state.settings.maxEntries) {
                state.entries = state.entries.slice(0, state.settings.maxEntries);
            }
        },
        saveSettings(state: HistoryState, settings: Partial<HistorySettings>) {
            Object.assign(state.settings, settings);
        }
    },
    getters: {
        all: ({ entries, settings }) => entries.slice(0, settings.maxEntries).map(deserializeHistoryEntry),
        latest: ({ entries }) => n => entries.slice(0, n).map(deserializeHistoryEntry),
    }
}

export default history;
