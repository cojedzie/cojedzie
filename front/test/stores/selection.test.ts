// @vitest-environment happy-dom
import { beforeEach, describe, expect, it } from "vitest";
import { createPinia, setActivePinia } from "pinia";
import { createApp, nextTick } from "vue";
import type { Stop } from "@/model";
import { MemoryStorage } from "@/stores/persistence/memory";
import { persistence } from "@/stores/persistence/plugin";
import { replaceStorages } from "@/stores/persistence/storages";
import { useSelectionStore } from "@/stores/selection";

const stop = (id: string) => ({ id, name: `Stop ${id}`, group: "Group", $type: "vnd.cojedzie.stop" }) as Stop;

function createStore(saved: Record<string, unknown> = {}) {
    replaceStorages({ session: new MemoryStorage(saved) });

    const pinia = createPinia();
    pinia.use(persistence());
    createApp({}).use(pinia);
    setActivePinia(pinia);

    return useSelectionStore();
}

describe("selection store", () => {
    beforeEach(() => {
        replaceStorages();
    });

    it("adds a single stop and a list of stops", () => {
        const store = createStore();

        store.add(stop("1"));
        store.add([stop("2"), stop("3")]);

        expect(store.stops.map(s => s.id)).toEqual(["1", "2", "3"]);
    });

    it("hydrates the stops from the session", async () => {
        const store = createStore({ selection: { stops: [stop("7")] } });

        await store.$hydrated;
        await nextTick();

        expect(store.stops.map(s => s.id)).toEqual(["7"]);
    });
});
