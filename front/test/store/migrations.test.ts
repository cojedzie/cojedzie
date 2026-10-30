// @vitest-environment happy-dom
import { beforeEach, describe, expect, it, vi } from "vitest";

const api = vi.hoisted(() => ({ get: vi.fn() }));
vi.mock("@/api", () => ({ default: api }));

import { migrate } from "@/store/migrations";

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
        api.get.mockImplementation(async (_endpoint, { query }) => ({
            data: query.id.map(id => stops[id]),
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

        await migrate("vuex");

        expect(api.get).toHaveBeenCalledOnce();
        expect(api.get.mock.calls[0][1].query).toEqual({ id: ["1", "2"] });

        const state = saved("vuex");
        expect(state["departures-settings"]).toEqual({ autorefresh: false });
        expect(state.favourites.favourites).toEqual([
            { id: "kept", name: "Home", stops: [stops["1"], stops["2"]] },
            { id: expect.any(String), name: "Work", stops: [stops["2"]] },
        ]);
        expect(saved("migrations")).toEqual(["202001261540_full_stop_in_state"]);
    });

    it("records the migration without calling the API when there are no favourites", async () => {
        window.localStorage.setItem("vuex", JSON.stringify({ favourites: { favourites: [] } }));

        await migrate("vuex");

        expect(api.get).not.toHaveBeenCalled();
        expect(saved("vuex")).toEqual({ favourites: { favourites: [] } });
        expect(saved("migrations")).toEqual(["202001261540_full_stop_in_state"]);
    });

    it("does not run a migration again", async () => {
        const state = { favourites: { favourites: [{ name: "Home", state: { stops: ["1"] } }] } };
        window.localStorage.setItem("vuex", JSON.stringify(state));
        window.localStorage.setItem("migrations", JSON.stringify(["202001261540_full_stop_in_state"]));

        await migrate("vuex");

        expect(api.get).not.toHaveBeenCalled();
        expect(saved("vuex")).toEqual(state);
    });
});
