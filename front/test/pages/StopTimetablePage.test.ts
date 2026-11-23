// @vitest-environment happy-dom
import { beforeEach, describe, expect, it, vi } from "vitest";
import { flushPromises, mount, type VueWrapper } from "@vue/test-utils";
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

const strzyza = { id: "d2", name: "Strzyża PKM" };
const wrzeszcz = { id: "d3", name: "Wrzeszcz" };

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

/** The link to the line's route, if the page shows one */
const routeLink = (wrapper: VueWrapper) => wrapper.findAll("a").find(link => link.text() === "Trasa");

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
                            { order: 2, track: { id: "t6", line: six, destination: strzyza } },
                        ],
                    };
                default:
                    return { data: [] };
            }
        });
    });

    it("leaves a whole stop's name and its directions to the page's heading", async () => {
        const { wrapper } = await mountPage("/trojmiasto/timetable/stops/s1?date=2026-11-13");

        expect(wrapper.find("header").exists()).toBe(false);
        expect(wrapper.findComponent({ name: "StopDestinations" }).exists()).toBe(false);
    });

    it("tells the layout which stop it loaded, so the breadcrumbs can name it", async () => {
        const { wrapper } = await mountPage("/trojmiasto/timetable/stops/s1");

        expect(wrapper.emitted("stop")).toEqual([[stop]]);
    });

    it("shows a stop's day of all lines, today without a date", async () => {
        const { wrapper } = await mountPage("/trojmiasto/timetable/stops/s1");
        const today = todayIn(Temporal.Now.timeZoneId()).toString();

        expect(timetableQueries()).toEqual([expect.objectContaining({ date: today, offset: 0 })]);
        expect(timetableQueries()[0]).not.toHaveProperty("line");
        expect(wrapper.find(".timetable").attributes("data-date")).toBe(today);
        expect(wrapper.find(".timetable").attributes("data-line-fixed")).toBe("false");
    });

    it("heads a line at the stop with the stop and the direction of the line's track through it", async () => {
        const { wrapper } = await mountPage("/trojmiasto/timetable/lines/l6/stops/s1");

        expect(wrapper.find("h2").text()).toBe("Brama Wyżynna 01");
        expect(wrapper.find(".direction").text()).toBe("Kierunek: Strzyża PKM");
        expect(wrapper.find("header").findComponent({ name: "StopDestinations" }).props("destinations")).toEqual(
            stop.destinations,
        );
    });

    it("shows one line's day on the line's route", async () => {
        const { wrapper } = await mountPage("/trojmiasto/timetable/lines/l6/stops/s1?date=2026-11-13");

        expect(timetableQueries()).toEqual([expect.objectContaining({ date: "2026-11-13", line: "l6" })]);
        expect(wrapper.find(".timetable").attributes("data-line-fixed")).toBe("true");
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

    describe("with the line page", () => {
        const routes = [
            ...baseRoutes,
            { path: "/:provider/timetable/lines/:line", name: "timetable-line", component: page },
        ];

        beforeEach(() => {
            const fallback = get.getMockImplementation()!;
            get.mockImplementation(async (endpoint: string, options: unknown) =>
                endpoint === "v1_stop_tracks"
                    ? {
                          data: [
                              { order: 1, track: { id: "t6-short", kind: "side", line: six, destination: wrzeszcz } },
                              { order: 2, track: { id: "t8", kind: "main", line: eight } },
                              { order: 3, track: { id: "t6", kind: "main", line: six, destination: strzyza } },
                          ],
                      }
                    : fallback(endpoint, options),
            );
        });

        it("links a line to its route through the stop, preferring a main track", async () => {
            const { wrapper } = await mountPage("/trojmiasto/timetable/lines/l6/stops/s1", { routes });

            expect(routeLink(wrapper)?.attributes("href")).toBe("/trojmiasto/timetable/lines/l6?track=t6");
        });

        it("names the direction of the track the route link opens", async () => {
            const main = await mountPage("/trojmiasto/timetable/lines/l6/stops/s1", { routes });
            expect(main.wrapper.find(".direction").text()).toBe("Kierunek: Strzyża PKM");

            const side = await mountPage("/trojmiasto/timetable/lines/l6/stops/s1?track=t6-short", { routes });
            expect(side.wrapper.find(".direction").text()).toBe("Kierunek: Wrzeszcz");
        });

        it("keeps the track in the URL when it runs through the stop", async () => {
            const kept = await mountPage("/trojmiasto/timetable/lines/l6/stops/s1?track=t6-short", { routes });
            expect(routeLink(kept.wrapper)?.attributes("href")).toBe("/trojmiasto/timetable/lines/l6?track=t6-short");

            const elsewhere = await mountPage("/trojmiasto/timetable/lines/l6/stops/s1?track=t8", { routes });
            expect(routeLink(elsewhere.wrapper)?.attributes("href")).toBe("/trojmiasto/timetable/lines/l6?track=t6");
        });
    });
});
