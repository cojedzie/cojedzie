// @vitest-environment happy-dom
import { beforeEach, describe, expect, it } from "vitest";
import { createPinia, setActivePinia } from "pinia";
import { createApp } from "vue";
import { MemoryStorage } from "@/stores/persistence/memory";
import { persistence } from "@/stores/persistence/plugin";
import { replaceStorages } from "@/stores/persistence/storages";
import { useDeparturesSettingsStore } from "@/stores/departures-settings";
import { useMessagesSettingsStore } from "@/stores/messages-settings";

function createPersistedPinia(saved: Record<string, unknown> = {}) {
    replaceStorages({ local: new MemoryStorage(saved) });

    const pinia = createPinia();
    pinia.use(persistence());
    createApp({}).use(pinia);
    setActivePinia(pinia);
}

describe("departures settings store", () => {
    beforeEach(() => replaceStorages());

    it("hydrates from its key", async () => {
        const saved = {
            autorefresh: false,
            autorefreshInterval: 20,
            displayedEntriesCount: 15,
            relativeTimes: true,
            relativeTimesForScheduled: false,
            relativeTimesLimit: 30,
            relativeTimesLimitEnabled: false,
        };
        createPersistedPinia({ "departures-settings": saved });
        const store = useDeparturesSettingsStore();

        await store.$hydrated;

        expect(store.$state).toEqual(saved);
    });
});

describe("messages settings store", () => {
    beforeEach(() => replaceStorages());

    it("hydrates from its key", async () => {
        const saved = { autorefresh: false, autorefreshInterval: 120, displayedEntriesCount: 3 };
        createPersistedPinia({ "messages-settings": saved });
        const store = useMessagesSettingsStore();

        await store.$hydrated;

        expect(store.$state).toEqual(saved);
    });
});
