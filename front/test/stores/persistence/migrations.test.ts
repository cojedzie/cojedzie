// @vitest-environment happy-dom
import { beforeEach, describe, expect, it, vi } from "vitest";

const api = vi.hoisted(() => ({ get: vi.fn() }));
vi.mock("@/api", () => ({ default: api }));

const sentry = vi.hoisted(() => ({ captureException: vi.fn() }));
vi.mock("@sentry/vue", () => sentry);

import { createPinia, setActivePinia } from "pinia";
import { createApp } from "vue";
import { persistence } from "@/stores/persistence/plugin";
import { replaceStorages } from "@/stores/persistence/storages";
import { useFavouritesStore } from "@/stores/favourites";
import { useDeparturesSettingsStore } from "@/stores/departures-settings";
import { useMessagesSettingsStore } from "@/stores/messages-settings";
import { useHistoryStore } from "@/stores/history";
import { migrate, migrations, type Migration } from "@/stores/persistence/migrations";
import { BrowserStorage } from "@/stores/persistence/browser";

const stops = {
    "1": { id: "1", name: "Brama Wyżynna", $type: "vnd.cojedzie.stop" },
    "2": { id: "2", name: "Dworzec Główny", $type: "vnd.cojedzie.stop" },
};

const stop = { id: "1", name: "Brama Wyżynna", group: "Brama Wyżynna", $type: "vnd.cojedzie.stop" };

// What the Vuex store kept in the "vuex" key before the split.
const snapshot = {
    favourites: { favourites: [{ id: "a", name: "Home", stops: [stop] }] },
    "departures-settings": {
        autorefresh: false,
        autorefreshInterval: 20,
        displayedEntriesCount: 15,
        relativeTimes: true,
        relativeTimesForScheduled: false,
        relativeTimesLimit: 30,
        relativeTimesLimitEnabled: false,
    },
    "messages-settings": {
        autorefresh: true,
        autorefreshInterval: 120,
        displayedEntriesCount: 3,
    },
    history: {
        entries: [{ stop, date: "2026-10-30T10:00:00.000Z" }],
        settings: { maxEntries: 5 },
    },
};

const SPLIT = "202610301200_split_vuex_key";
const STOP_2020 = "202001261540_full_stop_in_state";

function saved(key: string) {
    return JSON.parse(window.localStorage.getItem(key));
}

// the 2020 migration on its own, the split would move its result out of the vuex key
const fullStop = migrations[0];

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

        await migrate(new BrowserStorage(window.localStorage), [fullStop]);

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

    it("drops stops the API no longer knows", async () => {
        window.localStorage.setItem(
            "vuex",
            JSON.stringify({
                favourites: { favourites: [{ id: "a", name: "Home", state: { stops: ["1", "gone"] } }] },
            }),
        );

        await migrate(new BrowserStorage(window.localStorage), [fullStop]);

        expect(saved("vuex").favourites.favourites).toEqual([{ id: "a", name: "Home", stops: [stops["1"]] }]);
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

        await migrate(new BrowserStorage(window.localStorage), [fullStop]);

        expect(api.get).not.toHaveBeenCalled();
        expect(saved("vuex")).toEqual({ favourites: { favourites: [] } });
        expect(saved("migrations")).toEqual(["202001261540_full_stop_in_state"]);
    });

    it("keeps favourites that are already converted", async () => {
        const favourites = [{ id: "a", name: "Home", stops: [stops["1"]] }];
        window.localStorage.setItem("vuex", JSON.stringify({ favourites: { favourites } }));

        await migrate(new BrowserStorage(window.localStorage));

        expect(api.get).not.toHaveBeenCalled();
        expect(saved("favourites")).toEqual({ favourites });
        expect(saved("migrations")).toEqual([STOP_2020, SPLIT]);
    });

    it("does not run a migration again", async () => {
        const state = { favourites: { favourites: [{ name: "Home", state: { stops: ["1"] } }] } };
        window.localStorage.setItem("vuex", JSON.stringify(state));
        window.localStorage.setItem("migrations", JSON.stringify(["202001261540_full_stop_in_state"]));

        await migrate(new BrowserStorage(window.localStorage), [fullStop]);

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
        await migrate(new BrowserStorage(window.localStorage), [fullStop]);

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

    describe("splitting the vuex key", () => {
        it("splits today's vuex object into one key per store", async () => {
            window.localStorage.setItem("vuex", JSON.stringify(snapshot));
            window.localStorage.setItem("migrations", JSON.stringify([STOP_2020]));

            await migrate(new BrowserStorage(window.localStorage));

            expect(saved("favourites")).toEqual(snapshot.favourites);
            expect(saved("departures-settings")).toEqual(snapshot["departures-settings"]);
            expect(saved("messages-settings")).toEqual(snapshot["messages-settings"]);
            expect(saved("history")).toEqual(snapshot.history);
            expect(window.localStorage.getItem("vuex")).toBeNull();
            expect(saved("migrations")).toEqual([STOP_2020, SPLIT]);
        });

        it("skips parts that are missing", async () => {
            window.localStorage.setItem("vuex", JSON.stringify({ history: snapshot.history }));
            window.localStorage.setItem("migrations", JSON.stringify([STOP_2020]));

            await migrate(new BrowserStorage(window.localStorage));

            expect(saved("history")).toEqual(snapshot.history);
            expect(window.localStorage.getItem("favourites")).toBeNull();
            expect(window.localStorage.getItem("vuex")).toBeNull();
        });

        it("prefers the vuex value over an existing per-store key", async () => {
            window.localStorage.setItem("vuex", JSON.stringify({ history: snapshot.history }));
            window.localStorage.setItem("history", JSON.stringify({ entries: [], settings: { maxEntries: 1 } }));
            window.localStorage.setItem("migrations", JSON.stringify([STOP_2020]));

            await migrate(new BrowserStorage(window.localStorage));

            expect(saved("history")).toEqual(snapshot.history);
        });

        it("does nothing without a vuex key", async () => {
            window.localStorage.setItem("migrations", JSON.stringify([STOP_2020]));

            await migrate(new BrowserStorage(window.localStorage));

            expect(window.localStorage.length).toBe(1);
            expect(saved("migrations")).toEqual([STOP_2020, SPLIT]);
        });

        it("migrates 2020 data in two steps", async () => {
            window.localStorage.setItem(
                "vuex",
                JSON.stringify({
                    ...snapshot,
                    favourites: { favourites: [{ id: "kept", name: "Home", state: { stops: ["1", "2"] } }] },
                }),
            );

            await migrate(new BrowserStorage(window.localStorage));

            expect(saved("favourites").favourites).toEqual([
                { id: "kept", name: "Home", stops: [stops["1"], stops["2"]] },
            ]);
            expect(saved("history")).toEqual(snapshot.history);
            expect(window.localStorage.getItem("vuex")).toBeNull();
            expect(saved("migrations")).toEqual([STOP_2020, SPLIT]);
        });

        it("hydrates every saved store from a migrated vuex snapshot", async () => {
            window.localStorage.setItem("vuex", JSON.stringify(snapshot));
            window.localStorage.setItem("migrations", JSON.stringify([STOP_2020]));
            replaceStorages();

            await migrate(new BrowserStorage(window.localStorage));

            const pinia = createPinia();
            pinia.use(persistence());
            createApp({}).use(pinia);
            setActivePinia(pinia);

            const favourites = useFavouritesStore();
            const departures = useDeparturesSettingsStore();
            const messages = useMessagesSettingsStore();
            const history = useHistoryStore();
            await Promise.all([favourites.$hydrated, departures.$hydrated, messages.$hydrated, history.$hydrated]);

            expect(favourites.favourites).toEqual(snapshot.favourites.favourites);
            expect(departures.$state).toEqual(snapshot["departures-settings"]);
            expect(messages.$state).toEqual(snapshot["messages-settings"]);
            expect(history.settings).toEqual(snapshot.history.settings);
            expect(history.entries).toEqual(snapshot.history.entries);
            expect(history.all[0].date.toISOString()).toBe("2026-10-30T10:00:00.000Z");
        });
    });
});
