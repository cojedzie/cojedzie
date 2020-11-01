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
