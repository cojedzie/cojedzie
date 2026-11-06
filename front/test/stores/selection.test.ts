// @vitest-environment happy-dom
import { beforeEach, describe, expect, it, vi } from "vitest";
import { createPinia, setActivePinia } from "pinia";
import { createApp, nextTick } from "vue";
import type { Stop } from "@/model";
import { MemoryStorage } from "@/stores/persistence/memory";
import { persistence } from "@/stores/persistence/plugin";
import { replaceStorages } from "@/stores/persistence/storages";
import { useSelectionStore } from "@/stores/selection";

const get = vi.hoisted(() => vi.fn());
vi.mock("@/api", () => ({ default: { get } }));

function details(id: string) {
    return { data: { id, name: `Provider ${id}` } };
}

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

    describe("loading the provider", () => {
        beforeEach(() => {
            get.mockReset();
            get.mockImplementation((_, { params }) => Promise.resolve(details(params.provider)));
        });

        it("clears the stops when another provider loads", async () => {
            const store = createStore();
            await store.loadProvider("trojmiasto");
            store.add(stop("1"));

            const loading = store.loadProvider("poznan");

            expect(store.stops).toEqual([]);
            expect(store.provider).toBeNull();
            await loading;
            expect(store.provider.id).toBe("poznan");
        });

        it("keeps the stops when the same provider loads again", async () => {
            const store = createStore();
            await store.loadProvider("trojmiasto");
            store.add(stop("1"));

            await store.loadProvider("trojmiasto");

            expect(store.stops.map(s => s.id)).toEqual(["1"]);
        });

        it("keeps the stops restored from the session on the first load", async () => {
            const store = createStore();
            store.add(stop("1"));

            await store.loadProvider("trojmiasto");

            expect(store.stops.map(s => s.id)).toEqual(["1"]);
        });

        it("ignores the details of a provider it switched away from", async () => {
            const store = createStore();
            let resolveOld: (value: unknown) => void;
            get.mockImplementationOnce(() => new Promise(resolve => (resolveOld = resolve)));

            const old = store.loadProvider("trojmiasto");
            await store.loadProvider("poznan");
            resolveOld(details("trojmiasto"));
            await old;

            expect(store.provider.id).toBe("poznan");
        });
    });
});
