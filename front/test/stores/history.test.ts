// @vitest-environment happy-dom
import { beforeEach, describe, expect, it } from "vitest";
import { createPinia, setActivePinia } from "pinia";
import { createApp } from "vue";
import { Temporal } from "@/time";
import type { Stop } from "@/model";
import { MemoryStorage } from "@/stores/persistence/memory";
import { persistence } from "@/stores/persistence/plugin";
import { replaceStorages } from "@/stores/persistence/storages";
import { useHistoryStore } from "@/stores/history";

const stop = (id: string) => ({ id, name: `Stop ${id}`, group: "Group", $type: "vnd.cojedzie.stop" }) as Stop;

function createStore(saved: Record<string, unknown> = {}) {
    replaceStorages({ local: new MemoryStorage(saved) });

    const pinia = createPinia();
    pinia.use(persistence());
    createApp({}).use(pinia);
    setActivePinia(pinia);

    return useHistoryStore();
}

describe("history store", () => {
    beforeEach(() => replaceStorages());

    it("puts the newest entry first", () => {
        const store = createStore();

        store.push({ stop: stop("1"), date: Temporal.Instant.from("2026-10-30T10:00:00Z") });
        store.push({ stop: stop("2"), date: Temporal.Instant.from("2026-10-30T11:00:00Z") });

        expect(store.all.map(entry => entry.stop.id)).toEqual(["2", "1"]);
    });

    it("keeps one entry per stop", () => {
        const store = createStore();

        store.push({ stop: stop("1"), date: Temporal.Instant.from("2026-10-30T10:00:00Z") });
        store.push({ stop: stop("2"), date: Temporal.Instant.from("2026-10-30T11:00:00Z") });
        store.push({ stop: stop("1"), date: Temporal.Instant.from("2026-10-30T12:00:00Z") });

        expect(store.all.map(entry => entry.stop.id)).toEqual(["1", "2"]);
    });

    it("caps at maxEntries", () => {
        const store = createStore();
        store.saveSettings({ maxEntries: 2 });

        for (const id of ["1", "2", "3"]) {
            store.push({ stop: stop(id), date: Temporal.Now.instant() });
        }

        expect(store.entries.map(entry => entry.stop.id)).toEqual(["3", "2"]);
    });

    it("stores dates as ISO strings with milliseconds and returns instants", () => {
        const store = createStore();

        store.push({ stop: stop("1"), date: Temporal.Instant.from("2026-10-30T10:00:00Z") });

        expect(store.entries[0].date).toBe("2026-10-30T10:00:00.000Z");
        expect(store.latest(1)[0].date.equals(Temporal.Instant.from("2026-10-30T10:00:00Z"))).toBe(true);
    });

    it("hydrates from its key", async () => {
        const store = createStore({
            history: {
                entries: [{ stop: stop("1"), date: "2026-10-30T10:00:00.000Z" }],
                settings: { maxEntries: 5 },
            },
        });

        await store.$hydrated;

        expect(store.settings.maxEntries).toBe(5);
        expect(store.all[0].stop.id).toBe("1");
        // saved by moment, with milliseconds
        expect(store.all[0].date.equals(Temporal.Instant.from("2026-10-30T10:00:00Z"))).toBe(true);
    });
});
