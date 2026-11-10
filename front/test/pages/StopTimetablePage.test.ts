// @vitest-environment happy-dom
import { beforeEach, describe, expect, it, vi } from "vitest";
import { flushPromises, mount } from "@vue/test-utils";
import { createPinia, setActivePinia } from "pinia";
import { createMemoryHistory, createRouter, type RouteRecordRaw } from "vue-router";
import StopTimetablePage from "@/pages/StopTimetablePage.vue";
import { useSelectionStore } from "@/stores/selection";
import { useTimetableSettingsStore } from "@/stores/timetable-settings";
import { Temporal, todayIn } from "@/time";

const get = vi.hoisted(() => vi.fn());
vi.mock("@/api", () => ({ default: { get } }));

const line = (id: string, symbol: string) => ({ id, symbol, type: "tram", night: false, fast: false });
const six = line("l6", "6");
const eight = line("l8", "8");

const stop = {
    id: "s1",
    name: "Brama Wyżynna",
    variant: "01",
    destinations: [
        { stop: { id: "d1", name: "Jelitkowo" }, lines: [eight] },
        { stop: { id: "d2", name: "Strzyża PKM" }, lines: [six] },
    ],
};

const provider = {
    id: "trojmiasto",
    name: "Trójmiasto",
    timezone: "Europe/Warsaw",
    timetable: { from: "2020-01-01", to: "2030-12-31" },
};

const stubs = {
    UiIcon: true,
    StopDestinations: { name: "StopDestinations", props: ["destinations"], template: "<ul />" },
    StopLabel: { props: ["stop"], template: "<span class='stop-label'>{{ stop.name }} {{ stop.variant }}</span>" },
    LineSymbol: { props: ["line"], template: "<span class='symbol'>{{ line.symbol }}</span>" },
    StopTimetable: {
        props: ["departures", "date", "view", "grouping", "lineFixed", "now", "loading"],
        emits: ["update:date", "update:view", "update:grouping"],
        template: `<div class="timetable" :data-date="date.toString()" :data-view="view" :data-line-fixed="!!lineFixed">
            <button class="next-day" @click="$emit('update:date', date.add({ days: 1 }))" />
            <button class="hours" @click="$emit('update:view', 'hours')" />
        </div>`,
    },
};

const page = { template: "<div />" };
const baseRoutes: RouteRecordRaw[] = [
    { path: "/:provider/timetable/stops/:stop", name: "timetable-stop", component: page },
    { path: "/:provider/timetable/lines/:line/stops/:stop", name: "timetable-line-stop", component: page },
];

async function mountPage(path: string, { routes = baseRoutes, details = provider as unknown } = {}) {
    const pinia = createPinia();
    setActivePinia(pinia);
    useSelectionStore().provider = details as never;

    const router = createRouter({ history: createMemoryHistory(), routes });
    await router.push(path);
    await router.isReady();

    const wrapper = mount(StopTimetablePage, { global: { plugins: [pinia, router], stubs } });
    await flushPromises();

    return { wrapper, router };
}

const timetableQueries = () =>
    get.mock.calls.filter(([endpoint]) => endpoint === "v1_stop_timetable").map(([, options]) => options.query);

describe("StopTimetablePage", () => {
    beforeEach(() => {
        get.mockReset();
        get.mockImplementation(async (endpoint: string) => {
            switch (endpoint) {
                case "v1_stop_details":
                    return { data: stop };
                case "v1_stop_tracks":
                    return {
                        data: [
                            { order: 1, track: { id: "t8", line: eight } },
                            { order: 2, track: { id: "t6", line: six } },
                        ],
                    };
                default:
                    return { data: [] };
            }
        });
    });

    it("heads the page with the stop, its directions with their lines, and its lines", async () => {
        const { wrapper } = await mountPage("/trojmiasto/timetable/stops/s1?date=2026-11-13");

        expect(wrapper.find("h1").text()).toBe("Brama Wyżynna 01");
        expect(wrapper.findComponent({ name: "StopDestinations" }).props("destinations")).toEqual(stop.destinations);
        expect(wrapper.findAll(".chip").map(chip => chip.text())).toEqual(["Wszystkie", "6", "8"]);
    });

    it("shows a stop's day of all lines, today without a date", async () => {
        const { wrapper } = await mountPage("/trojmiasto/timetable/stops/s1");
        const today = todayIn(Temporal.Now.timeZoneId()).toString();

        expect(timetableQueries()).toEqual([expect.objectContaining({ date: today, offset: 0 })]);
        expect(timetableQueries()[0]).not.toHaveProperty("line");
        expect(wrapper.find(".timetable").attributes("data-date")).toBe(today);
        expect(wrapper.find(".timetable").attributes("data-line-fixed")).toBe("false");
        expect(wrapper.find(".chip[aria-current=page]").text()).toBe("Wszystkie");
    });

    it("shows one line's day on the line's route, with its chip pressed", async () => {
        const { wrapper } = await mountPage("/trojmiasto/timetable/lines/l6/stops/s1?date=2026-11-13");

        expect(timetableQueries()).toEqual([expect.objectContaining({ date: "2026-11-13", line: "l6" })]);
        expect(wrapper.find(".timetable").attributes("data-line-fixed")).toBe("true");
        expect(wrapper.find(".chip[aria-current=page]").text()).toBe("6");
    });

    it("links the chips to the line's and the whole stop's routes, keeping the date", async () => {
        const { wrapper } = await mountPage("/trojmiasto/timetable/lines/l6/stops/s1?date=2026-11-13");

        expect(wrapper.findAll(".chip").map(chip => chip.attributes("href"))).toEqual([
            "/trojmiasto/timetable/stops/s1?date=2026-11-13",
            "/trojmiasto/timetable/lines/l6/stops/s1?date=2026-11-13",
            "/trojmiasto/timetable/lines/l8/stops/s1?date=2026-11-13",
        ]);
    });

    it("puts another date picked in the URL and fetches its day", async () => {
        const { wrapper, router } = await mountPage("/trojmiasto/timetable/stops/s1?date=2026-11-13");

        await wrapper.find(".next-day").trigger("click");
        await flushPromises();

        expect(router.currentRoute.value.query).toEqual({ date: "2026-11-14" });
        expect(timetableQueries().map(query => query.date)).toEqual(["2026-11-13", "2026-11-14"]);
        expect(wrapper.find(".timetable").attributes("data-date")).toBe("2026-11-14");
    });

    it("shows a stop as a list and a line as hour rows until a view is picked", async () => {
        const stop = await mountPage("/trojmiasto/timetable/stops/s1?date=2026-11-13");
        expect(stop.wrapper.find(".timetable").attributes("data-view")).toBe("list");

        const line = await mountPage("/trojmiasto/timetable/lines/l6/stops/s1?date=2026-11-13");
        expect(line.wrapper.find(".timetable").attributes("data-view")).toBe("hours");
    });

    it("remembers the view picked, for stops and lines alike", async () => {
        const { wrapper, router } = await mountPage("/trojmiasto/timetable/lines/l6/stops/s1?date=2026-11-13");

        await wrapper.find(".hours").trigger("click");
        await router.push("/trojmiasto/timetable/stops/s1?date=2026-11-13");
        await flushPromises();

        expect(useTimetableSettingsStore().view).toBe("hours");
        expect(wrapper.find(".timetable").attributes("data-view")).toBe("hours");
    });

    it("says so when the provider has no timetable", async () => {
        const { wrapper } = await mountPage("/trojmiasto/timetable/stops/s1", {
            details: { ...provider, timetable: null },
        });

        expect(wrapper.find(".timetable").exists()).toBe(false);
        expect(wrapper.find(".no-timetable").exists()).toBe(true);
        expect(timetableQueries()).toEqual([]);
    });

    it("links a line to its route through the stop once the line page exists", async () => {
        const without = await mountPage("/trojmiasto/timetable/lines/l6/stops/s1");
        expect(without.wrapper.find(".route-link").exists()).toBe(false);

        const { wrapper } = await mountPage("/trojmiasto/timetable/lines/l6/stops/s1", {
            routes: [
                ...baseRoutes,
                { path: "/:provider/timetable/lines/:line", name: "timetable-line", component: page },
            ],
        });
        expect(wrapper.find(".route-link").attributes("href")).toBe("/trojmiasto/timetable/lines/l6?track=t6");
    });
});
