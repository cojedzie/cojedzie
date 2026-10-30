// @vitest-environment happy-dom
import { beforeEach, describe, expect, it, vi } from "vitest";

const api = vi.hoisted(() => ({ get: vi.fn() }));
vi.mock("@/api", () => ({ default: api }));

const sentry = vi.hoisted(() => ({ captureException: vi.fn() }));
vi.mock("@sentry/vue", () => sentry);

import { migrate, type Migration } from "@/stores/persistence/migrations";
import { BrowserStorage } from "@/stores/persistence/browser";

const stops = {
    "1": { id: "1", name: "Brama Wyżynna", $type: "vnd.cojedzie.stop" },
    "2": { id: "2", name: "Dworzec Główny", $type: "vnd.cojedzie.stop" },
};

function saved(key: string) {
    return JSON.parse(window.localStorage.getItem(key));
}

describe("persisted state migrations", () => {
    beforeEach(() => {
        window.localStorage.clear();
        api.get.mockReset();
        sentry.captureException.mockReset();
        vi.spyOn(console, "error").mockImplementation(() => {});
        api.get.mockImplementation(async (_endpoint, { query }) => ({
            data: query.id.map(id => stops[id]).filter(Boolean),
        }));
    });

    it("replaces stop ids of favourites saved before 2020 with the stops", async () => {
        window.localStorage.setItem(
            "vuex",
            JSON.stringify({
                favourites: {
                    favourites: [
                        { id: "kept", name: "Home", state: { stops: ["1", "2"] } },
                        { name: "Work", state: { stops: ["2"] } },
                    ],
                },
                "departures-settings": { autorefresh: false },
            }),
        );

        await migrate(new BrowserStorage(window.localStorage));

        expect(api.get).toHaveBeenCalledOnce();
        expect(api.get.mock.calls[0][0]).toBe("v1_stop_list");
        // the client context is not bound yet, the provider has to be given explicitly
        expect(api.get.mock.calls[0][1]).toMatchObject({
            params: { provider: "trojmiasto" },
            query: { id: ["1", "2"] },
            maxRetries: 1,
        });

        const state = saved("vuex");
        expect(state["departures-settings"]).toEqual({ autorefresh: false });
        expect(state.favourites.favourites).toEqual([
            { id: "kept", name: "Home", stops: [stops["1"], stops["2"]] },
            { id: expect.any(String), name: "Work", stops: [stops["2"]] },
        ]);
        expect(saved("migrations")).toEqual(["202001261540_full_stop_in_state"]);
    });

    it("fails without recording when the stops cannot be fetched", async () => {
        const state = { favourites: { favourites: [{ name: "Home", state: { stops: ["1"] } }] } };
        window.localStorage.setItem("vuex", JSON.stringify(state));
        // the load balanced client resolves nothing once it runs out of retries
        api.get.mockResolvedValue(undefined);

        await migrate(new BrowserStorage(window.localStorage));

        expect(saved("vuex")).toEqual(state);
        expect(window.localStorage.getItem("migrations")).toBeNull();
        expect(sentry.captureException).toHaveBeenCalled();
    });

    it("records the migration without calling the API when there are no favourites", async () => {
        window.localStorage.setItem("vuex", JSON.stringify({ favourites: { favourites: [] } }));

        await migrate(new BrowserStorage(window.localStorage));

        expect(api.get).not.toHaveBeenCalled();
        expect(saved("vuex")).toEqual({ favourites: { favourites: [] } });
        expect(saved("migrations")).toEqual(["202001261540_full_stop_in_state"]);
    });

    it("does not run a migration again", async () => {
        const state = { favourites: { favourites: [{ name: "Home", state: { stops: ["1"] } }] } };
        window.localStorage.setItem("vuex", JSON.stringify(state));
        window.localStorage.setItem("migrations", JSON.stringify(["202001261540_full_stop_in_state"]));

        await migrate(new BrowserStorage(window.localStorage));

        expect(api.get).not.toHaveBeenCalled();
        expect(saved("vuex")).toEqual(state);
    });

    it("stops at a failing migration and retries it next time", async () => {
        const state = { favourites: { favourites: [{ name: "Home", state: { stops: ["1"] } }] } };
        window.localStorage.setItem("vuex", JSON.stringify(state));
        window.localStorage.setItem("migrations", JSON.stringify([]));
        api.get.mockRejectedValue(new Error("offline"));

        await expect(migrate(new BrowserStorage(window.localStorage))).resolves.toBeUndefined();

        expect(saved("vuex")).toEqual(state);
        expect(saved("migrations")).toEqual([]);
        expect(sentry.captureException).toHaveBeenCalled();

        api.get.mockImplementation(async (_endpoint, { query }) => ({
            data: query.id.map(id => stops[id]).filter(Boolean),
        }));
        await migrate(new BrowserStorage(window.localStorage));

        expect(saved("vuex").favourites.favourites[0].stops).toEqual([stops["1"]]);
        expect(saved("migrations")).toEqual(["202001261540_full_stop_in_state"]);
    });

    it("runs migrations in order and records each after it succeeds", async () => {
        const list: Migration[] = [
            {
                name: "first",
                up: async storage => storage.save("a", 1),
            },
            {
                name: "second",
                up: async storage => storage.save("b", ((await storage.load<number>("a")) ?? 0) + 1),
            },
        ];

        await migrate(new BrowserStorage(window.localStorage), list);

        expect(saved("a")).toBe(1);
        expect(saved("b")).toBe(2);
        expect(saved("migrations")).toEqual(["first", "second"]);
    });
});
