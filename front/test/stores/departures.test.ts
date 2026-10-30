import { beforeEach, describe, expect, it, vi } from "vitest";
import { createPinia, setActivePinia } from "pinia";
import moment from "moment";
import type { Stop } from "@/model";
import { useDeparturesStore } from "@/stores/departures";
import { useDeparturesSettingsStore } from "@/stores/departures-settings";
import { useSelectionStore } from "@/stores/selection";

const get = vi.hoisted(() => vi.fn());
vi.mock("@/api", () => ({ default: { get } }));

const stop = (id: string) => ({ id, name: `Stop ${id}`, group: "Group", $type: "vnd.cojedzie.stop" }) as Stop;

const departure = {
    key: "d1",
    display: "Oliwa",
    line: { id: "l", symbol: "6", type: "tram" },
    stop: stop("1"),
    delay: 60,
    scheduled: "2026-10-30T12:00:00+01:00",
    estimated: "2026-10-30T12:01:00+01:00",
};

describe("departures store", () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        get.mockReset();
    });

    it("does nothing without selected stops", async () => {
        const store = useDeparturesStore();

        await store.update();

        expect(get).not.toHaveBeenCalled();
        expect(store.state).toBe("not-initialized");
    });

    it("requests departures of the selected stops with the configured limit", async () => {
        get.mockResolvedValue({ data: [] });
        useSelectionStore().replace([stop("1"), stop("2")]);
        useDeparturesSettingsStore().update({ displayedEntriesCount: 12 });

        await useDeparturesStore().update();

        expect(get).toHaveBeenCalledWith("v1_departure_list", {
            version: "^1.0",
            query: { stop: ["1", "2"], limit: 12 },
        });
    });

    it("parses the times into local moments and becomes ready", async () => {
        get.mockResolvedValue({ data: [departure, { ...departure, key: "d2", estimated: null }] });
        useSelectionStore().replace([stop("1")]);
        const store = useDeparturesStore();
        const before = moment();

        await store.update();

        expect(store.state).toBe("ready");
        expect(store.lastUpdate.isSameOrAfter(before)).toBe(true);
        expect(store.departures.map(d => d.key)).toEqual(["d1", "d2"]);
        expect(store.departures[0].scheduled.isSame(moment("2026-10-30T11:00:00Z"))).toBe(true);
        expect(store.departures[0].estimated.isSame(moment("2026-10-30T11:01:00Z"))).toBe(true);
        expect(store.departures[1].estimated).toBeNull();
    });

    it("is fetching while the request runs", async () => {
        let resolve: (value: unknown) => void;
        get.mockReturnValue(new Promise(r => (resolve = r)));
        useSelectionStore().replace([stop("1")]);
        const store = useDeparturesStore();

        const update = store.update();
        expect(store.state).toBe("fetching");

        resolve({ data: [] });
        await update;
        expect(store.state).toBe("ready");
    });

    it("sets the error when the request fails", async () => {
        get.mockRejectedValue({ status: 500 });
        useSelectionStore().replace([stop("1")]);
        const store = useDeparturesStore();

        await store.update();

        expect(store.state).toBe("error");
        expect(store.error).toBe(JSON.stringify({ status: 500 }));
    });
});
