// @vitest-environment happy-dom
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";

// What the store keeps in the browser today: history in localStorage, the selected stops
// in sessionStorage. Users' saved data has this shape, so the store must keep reading and writing it.

const stop = { id: "1", name: "Brama Wyżynna", group: "Brama Wyżynna", $type: "vnd.cojedzie.stop" };

const local = {
    history: {
        entries: [{ stop, date: "2026-10-30T10:00:00.000Z" }],
        settings: { maxEntries: 5 },
    },
};

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

        window.localStorage.setItem("vuex", JSON.stringify(local));
        window.sessionStorage.setItem("vuex", JSON.stringify(session));
    });

    afterEach(() => {
        vi.useRealTimers();
        vi.unstubAllGlobals();
        window.localStorage.clear();
        window.sessionStorage.clear();
    });

    it("restores history from localStorage", async () => {
        const store = await loadStore();

        expect(store.state.history.settings).toEqual({ maxEntries: 5 });

        const [entry] = store.getters["history/all"];
        expect(entry.stop).toEqual(stop);
        expect(entry.date.toISOString()).toBe("2026-10-30T10:00:00.000Z");
    });

    it("restores the selected stops from sessionStorage", async () => {
        const store = await loadStore();

        expect(store.state.stops).toEqual([stop]);
    });

    it("saves changes in the same format", async () => {
        const store = await loadStore();
        const other = { ...stop, id: "2", name: "Dworzec Główny" };

        store.commit("history/push", { stop: other, date: (await import("moment")).default("2026-10-31T08:00:00Z") });
        store.commit("add", [other]);

        expect(JSON.parse(window.localStorage.getItem("vuex"))).toEqual({
            ...local,
            history: {
                ...local.history,
                entries: [{ stop: other, date: "2026-10-31T08:00:00.000Z" }, ...local.history.entries],
            },
        });
        expect(JSON.parse(window.sessionStorage.getItem("vuex"))).toEqual({ stops: [stop, other] });
    });
});
