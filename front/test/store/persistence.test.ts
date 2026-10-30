// @vitest-environment happy-dom
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";

// What the store keeps in the browser today: the selected stops in sessionStorage. Users' saved data has this shape, so the store must keep reading and writing it.

const stop = { id: "1", name: "Brama Wyżynna", group: "Brama Wyżynna", $type: "vnd.cojedzie.stop" };

const session = { stops: [stop] };

async function loadStore() {
    vi.resetModules();
    return (await import("@/store")).default;
}

describe("persisted store state", () => {
    beforeEach(() => {
        // the load balancer starts polling the API when the store is created
        vi.useFakeTimers();
        // the runtime config the server puts into every page
        vi.stubGlobal("CoJedzie", { api: { base: "http://localhost", hub: "http://localhost" } });

        window.sessionStorage.setItem("vuex", JSON.stringify(session));
    });

    afterEach(() => {
        vi.useRealTimers();
        vi.unstubAllGlobals();
        window.localStorage.clear();
        window.sessionStorage.clear();
    });

    it("restores the selected stops from sessionStorage", async () => {
        const store = await loadStore();

        expect(store.state.stops).toEqual([stop]);
    });

    it("saves changes in the same format", async () => {
        const store = await loadStore();
        const other = { ...stop, id: "2", name: "Dworzec Główny" };

        store.commit("add", [other]);

        expect(JSON.parse(window.sessionStorage.getItem("vuex"))).toEqual({ stops: [stop, other] });
    });
});
