// @vitest-environment happy-dom
import { afterEach, beforeEach, describe, expect, it } from "vitest";
import { createPinia, setActivePinia } from "pinia";
import { createApp, nextTick } from "vue";
import moment from "moment";
import type { Stop } from "@/model";
import { persistence, flushPersistence } from "@/stores/persistence/plugin";
import { replaceStorages } from "@/stores/persistence/storages";
import { useFavouritesStore } from "@/stores/favourites";
import { useDeparturesSettingsStore } from "@/stores/departures-settings";
import { useMessagesSettingsStore } from "@/stores/messages-settings";
import { useHistoryStore } from "@/stores/history";

// Users' saved data has this shape, so the stores must keep reading and writing it.

const stop = { id: "1", name: "Brama Wyżynna", group: "Brama Wyżynna", $type: "vnd.cojedzie.stop" } as Stop;

function saved(key: string) {
    return JSON.parse(window.localStorage.getItem(key));
}

// Saving starts once the store has loaded, and it is queued after the change is flushed.
async function change<T extends { $hydrated: Promise<void> }>(store: T, update: (store: T) => void) {
    await store.$hydrated;
    update(store);
    await nextTick();
    await flushPersistence();
}

describe("persisted store format", () => {
    beforeEach(() => {
        window.localStorage.clear();
        replaceStorages();

        const pinia = createPinia();
        pinia.use(persistence());
        createApp({}).use(pinia);
        setActivePinia(pinia);
    });

    afterEach(() => window.localStorage.clear());

    it("saves favourites under the favourites key", async () => {
        await change(useFavouritesStore(), store => store.add({ id: "a", name: "Home", stops: [stop] }));

        expect(saved("favourites")).toEqual({ favourites: [{ id: "a", name: "Home", stops: [stop] }] });
    });

    it("saves departures settings as a flat object", async () => {
        await change(useDeparturesSettingsStore(), store =>
            store.update({ autorefresh: false, autorefreshInterval: 20 }),
        );

        expect(saved("departures-settings")).toEqual({
            autorefresh: false,
            autorefreshInterval: 20,
            displayedEntriesCount: 10,
            relativeTimes: false,
            relativeTimesForScheduled: true,
            relativeTimesLimit: 40,
            relativeTimesLimitEnabled: true,
        });
    });

    it("saves messages settings as a flat object", async () => {
        await change(useMessagesSettingsStore(), store => store.update({ displayedEntriesCount: 3 }));

        expect(saved("messages-settings")).toEqual({
            autorefresh: true,
            autorefreshInterval: 60,
            displayedEntriesCount: 3,
        });
    });

    it("saves history entries with ISO dates and the settings", async () => {
        await change(useHistoryStore(), history => {
            history.saveSettings({ maxEntries: 5 });
            history.push({ stop, date: moment("2026-10-31T08:00:00Z") });
        });

        expect(saved("history")).toEqual({
            entries: [{ stop, date: "2026-10-31T08:00:00.000Z" }],
            settings: { maxEntries: 5 },
        });
    });
});
