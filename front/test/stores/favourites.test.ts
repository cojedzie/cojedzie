// @vitest-environment happy-dom
import { beforeEach, describe, expect, it } from "vitest";
import { createPinia, setActivePinia } from "pinia";
import { createApp } from "vue";
import type { Stop } from "@/model";
import { MemoryStorage } from "@/stores/persistence/memory";
import { persistence } from "@/stores/persistence/plugin";
import { replaceStorages } from "@/stores/persistence/storages";
import { useFavouritesStore } from "@/stores/favourites";

const stop = { id: "1", name: "Brama Wyżynna", group: "Brama Wyżynna", $type: "vnd.cojedzie.stop" } as Stop;

function createStore(saved: Record<string, unknown> = {}) {
    replaceStorages({ local: new MemoryStorage(saved) });

    const pinia = createPinia();
    pinia.use(persistence());
    createApp({}).use(pinia);
    setActivePinia(pinia);

    return useFavouritesStore();
}

describe("favourites store", () => {
    beforeEach(() => replaceStorages());

    it("replaces a favourite with the same name and keeps its id", () => {
        const store = createStore();
        store.add({ id: "a", name: "Home", stops: [stop] });

        store.add({ id: "b", name: "Home", stops: [] });

        expect(store.favourites).toEqual([{ id: "a", name: "Home", stops: [] }]);
    });

    it("removes a favourite", () => {
        const store = createStore();
        const home = { id: "a", name: "Home", stops: [stop] };
        const work = { id: "b", name: "Work", stops: [stop] };
        store.add(home);
        store.add(work);

        store.remove(store.favourites[0]);

        expect(store.favourites).toEqual([work]);
    });

    it("hydrates from the favourites key", async () => {
        const saved = [{ id: "a", name: "Home", stops: [stop] }];
        const store = createStore({ favourites: { favourites: saved } });

        await store.$hydrated;

        expect(store.favourites).toEqual(saved);
    });
});
