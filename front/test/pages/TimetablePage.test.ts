// @vitest-environment happy-dom
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { flushPromises, mount } from "@vue/test-utils";
import { createPinia, setActivePinia } from "pinia";
import { createMemoryHistory, createRouter } from "vue-router";
import TimetablePage from "@/pages/TimetablePage.vue";
import { useHistoryStore } from "@/stores/history";
import { Temporal } from "@/time";

const get = vi.hoisted(() => vi.fn());
vi.mock("@/api", () => ({ default: { get } }));

const stop = (id: string, name: string, variant: string) => ({ id, name, variant, $type: "vnd.cojedzie.stop" });
const bramaUp = stop("s1", "Brama Wyżynna", "01");
const bramaDown = stop("s2", "Brama Wyżynna", "02");
const bramka = stop("s3", "Bramka", "01");

const groups = [
    { name: "Brama Wyżynna", stops: [bramaUp, bramaDown] },
    { name: "Bramka", stops: [bramka] },
];

const line = (id: string, symbol: string, type = "bus", night = false) => ({
    id,
    symbol,
    type,
    night,
    fast: false,
    $type: "vnd.cojedzie.line",
});
const lines = [line("l1", "1", "tram"), line("l10", "10"), line("l111", "111")];

const lawendowe = stop("s4", "Lawendowe Wzgórze", "01");
const strzyza = stop("s5", "Strzyża PKM", "01");
const oliwa = stop("s6", "Oliwa", "01");

type Stop = ReturnType<typeof stop>;
const track = (id: string, onLine: string, kind: string, origin: Stop | undefined, destination: Stop) => ({
    id,
    kind,
    description: "",
    line: lines.find(({ id }) => id === onLine)!,
    origin,
    destination,
    $type: "vnd.cojedzie.track",
});
/** In the API's order, not the lines': 111 first; line 10 has a side track only, 111's main track no origin */
const tracks = [
    track("t111", "l111", "main", undefined, oliwa),
    track("t1-out", "l1", "main", lawendowe, strzyza),
    track("t1-short", "l1", "side", lawendowe, oliwa),
    track("t1-in", "l1", "main", strzyza, lawendowe),
    track("t10-short", "l10", "side", oliwa, strzyza),
];

/** The tracks of the lines the query names, as the API filters them */
const tracksOf = (query: { line: string }) => tracks.filter(({ line }) => query.line.split(",").includes(line.id));

const stubs = {
    UiIcon: true,
    UiTooltip: true,
    StopPickerEntry: {
        props: ["stop"],
        template: '<div class="entry"><slot name="primary-action" />{{ stop.name }} {{ stop.variant }}</div>',
    },
};

const routes = [
    {
        path: "/:provider/timetable",
        name: "timetable",
        component: TimetablePage,
        children: [
            {
                path: "stops/:stop",
                name: "timetable-stop",
                component: { template: "<p class='stop-page'>{{ $route.params.stop }}</p>" },
            },
            {
                path: "lines/:line/stops/:stop",
                name: "timetable-line-stop",
                component: { template: "<p class='stop-page'>{{ $route.params.stop }}</p>" },
            },
        ],
    },
];

const linePage = {
    path: "lines/:line",
    name: "timetable-line",
    component: { template: "<p class='line-page'>{{ $route.params.line }}</p>" },
};

/** The routes once the line page (#157) exists */
const routesWithLinePage = [{ ...routes[0], children: [...routes[0].children, linePage] }];

async function mountSection(
    path = "/trojmiasto/timetable",
    recent: ReturnType<typeof stop>[] = [],
    withRoutes: typeof routes = routes,
) {
    const pinia = createPinia();
    setActivePinia(pinia);
    for (const chosen of [...recent].reverse()) {
        useHistoryStore().push({ stop: chosen, date: Temporal.Instant.from("2026-11-10T08:00:00Z") });
    }

    const router = createRouter({ history: createMemoryHistory(), routes: withRoutes });
    await router.push(path);
    await router.isReady();

    const wrapper = mount({ template: "<router-view />" }, { global: { plugins: [pinia, router], stubs } });
    await flushPromises();

    return { wrapper, router, history: useHistoryStore() };
}

async function type(wrapper: Awaited<ReturnType<typeof mountSection>>["wrapper"], value: string) {
    await wrapper.find("input").setValue(value);
    await vi.advanceTimersByTimeAsync(400);
    await flushPromises();
}

const lineQueries = () =>
    get.mock.calls.filter(([endpoint]) => endpoint === "v1_line_list").map(([, options]) => options.query);

const trackQueries = () =>
    get.mock.calls.filter(([endpoint]) => endpoint === "v1_track_list").map(([, options]) => options.query);

const stopGroupQueries = () =>
    get.mock.calls.filter(([endpoint]) => endpoint === "v1_stop_group_list").map(([, options]) => options.query);

describe("TimetablePage's search", () => {
    beforeEach(() => {
        vi.useFakeTimers();
        get.mockReset();
        get.mockImplementation(async (endpoint: string, options: { query: { line: string } }) => {
            switch (endpoint) {
                case "v1_stop_group_list":
                    return { data: groups };
                case "v1_line_list":
                    return { data: lines };
                case "v1_track_list":
                    return { data: tracksOf(options.query) };
                default:
                    return { data: { id: "trojmiasto", timezone: "Europe/Warsaw" } };
            }
        });
    });

    afterEach(() => vi.useRealTimers());

    it("finds stops by name from three characters, grouped, and opens a stop's timetable", async () => {
        const { wrapper, router } = await mountSection();

        expect(wrapper.find("h2").text()).toBe("Rozkłady jazdy");

        await type(wrapper, "Br");
        expect(stopGroupQueries()).toEqual([]);

        await type(wrapper, "Bra");
        expect(stopGroupQueries()).toEqual([{ name: "Bra", embed: "destinations" }]);

        const found = wrapper.find("section[aria-label=Przystanki]");
        expect(found.findAll("h3").map(heading => heading.text())).toEqual(["Brama Wyżynna", "Bramka"]);
        expect(found.findAll(".entry").map(entry => entry.text())).toEqual([
            "Brama Wyżynna 01",
            "Brama Wyżynna 02",
            "Bramka 01",
        ]);

        await found.findAll(".entry a")[1].trigger("click");
        await flushPromises();

        expect(router.currentRoute.value.fullPath).toBe("/trojmiasto/timetable/stops/s2");
        expect(wrapper.find(".stop-page").text()).toBe("s2");
    });

    it("links a group's stops one by one, without the date, and never the group as a whole", async () => {
        const { wrapper } = await mountSection("/trojmiasto/timetable?date=2026-11-13");

        await type(wrapper, "Bra");

        const found = wrapper.find("section[aria-label=Przystanki]");
        expect(found.findAll("a").map(link => link.attributes("href"))).toEqual([
            "/trojmiasto/timetable/stops/s1",
            "/trojmiasto/timetable/stops/s2",
            "/trojmiasto/timetable/stops/s3",
        ]);
        expect(found.findAll("h3 a, h3 button")).toHaveLength(0);
    });

    it("records a stop chosen in the results as the most recent one", async () => {
        const { wrapper, history } = await mountSection("/trojmiasto/timetable", [bramka, bramaUp]);

        await type(wrapper, "Bra");
        await wrapper.findAll("section[aria-label=Przystanki] .entry a")[0].trigger("click");

        expect(history.all.map(entry => entry.stop.id)).toEqual(["s1", "s3"]);
    });

    it("shows the recent stops below three characters, opening them without reordering", async () => {
        const { wrapper, router, history } = await mountSection("/trojmiasto/timetable", [bramka, bramaUp]);

        const recent = () => wrapper.find("section[aria-label=Przystanki]");
        expect(recent().find("h3").text()).toBe("Ostatnio wybierane");
        expect(
            recent()
                .findAll(".entry")
                .map(entry => entry.text()),
        ).toEqual(["Bramka 01", "Brama Wyżynna 01"]);

        await type(wrapper, "Br");
        expect(recent().find("h3").text()).toBe("Ostatnio wybierane");

        await recent().findAll(".entry a")[1].trigger("click");
        await flushPromises();

        expect(router.currentRoute.value.fullPath).toBe("/trojmiasto/timetable/stops/s1");
        expect(history.all.map(entry => entry.stop.id)).toEqual(["s3", "s1"]);
    });

    it("hides the stops section when nothing is typed and no stop was ever chosen", async () => {
        const { wrapper } = await mountSection();

        expect(wrapper.find("section[aria-label=Przystanki]").exists()).toBe(false);
    });

    it("keeps the found stops while a longer query is searched", async () => {
        const { wrapper } = await mountSection();

        await type(wrapper, "Bra");
        await wrapper.find("input").setValue("Bram");
        await flushPromises();

        expect(wrapper.findAll("section[aria-label=Przystanki] .entry").map(entry => entry.text())).toEqual([
            "Brama Wyżynna 01",
            "Brama Wyżynna 02",
            "Bramka 01",
        ]);
    });

    it("says when no stop matches", async () => {
        get.mockImplementation(async (endpoint: string) => ({ data: endpoint === "v1_stop_group_list" ? [] : {} }));
        const { wrapper } = await mountSection();

        await type(wrapper, "Xyz");

        expect(wrapper.find("section[aria-label=Przystanki]").text()).toBe("Brak przystanków dla „Xyz”");
    });

    it("drops a late answer to an older query", async () => {
        let answerOld: (value: unknown) => void = () => {};
        get.mockImplementation(async (endpoint: string, options: { query?: { name?: string } }) => {
            if (endpoint !== "v1_stop_group_list") {
                return { data: {} };
            }

            if (options.query?.name === "Bra") {
                return new Promise(resolve => (answerOld = resolve));
            }

            return { data: [{ name: "Bramka", stops: [bramka] }] };
        });
        const { wrapper } = await mountSection();

        await type(wrapper, "Bra");
        await type(wrapper, "Bramk");
        answerOld({ data: groups });
        await flushPromises();

        expect(wrapper.findAll(".entry").map(entry => entry.text())).toEqual(["Bramka 01"]);
    });

    it("stays next to an opened stop with its query and results, marking the stop current", async () => {
        const { wrapper, router } = await mountSection();

        await type(wrapper, "Bra");
        await wrapper.findAll("section[aria-label=Przystanki] .entry a")[1].trigger("click");
        await flushPromises();

        expect(router.currentRoute.value.name).toBe("timetable-stop");
        expect(wrapper.find(".stop-page").text()).toBe("s2");
        expect((wrapper.find("input").element as HTMLInputElement).value).toBe("Bra");
        expect(stopGroupQueries()).toEqual([{ name: "Bra", embed: "destinations" }]);

        const links = wrapper.findAll("section[aria-label=Przystanki] .entry a");
        expect(links.map(link => link.attributes("aria-current"))).toEqual([undefined, "page", undefined]);
    });

    it("marks the open stop current in the recent stops, also at a line's stop", async () => {
        const { wrapper } = await mountSection("/trojmiasto/timetable/lines/l1/stops/s1", [bramka, bramaUp]);

        const links = wrapper.findAll("section[aria-label=Przystanki] .entry a");
        expect(links.map(link => link.attributes("href"))).toEqual([
            "/trojmiasto/timetable/stops/s3",
            "/trojmiasto/timetable/stops/s1",
        ]);
        expect(links.map(link => link.attributes("aria-current"))).toEqual([undefined, "page"]);
    });

    it("asks to pick a stop or a line while none is open", async () => {
        const { wrapper, router } = await mountSection();

        expect(wrapper.text()).toContain("Wybierz przystanek albo linię");

        await router.push("/trojmiasto/timetable/stops/s1");
        await flushPromises();

        expect(wrapper.text()).not.toContain("Wybierz przystanek albo linię");
    });

    it("finds lines by symbol from one character, before the stops, listing their main tracks", async () => {
        const { wrapper, router } = await mountSection("/trojmiasto/timetable", [bramka], routesWithLinePage);

        await type(wrapper, "1");
        expect(lineQueries()).toEqual([{ symbol: "1" }]);
        expect(trackQueries()).toEqual([{ line: "l1,l10,l111", limit: 100 }]);

        const found = wrapper.find("section[aria-label=Linie]");
        expect(found.findAll("a").map(link => [link.text(), link.attributes("href")])).toEqual([
            ["1 Lawendowe Wzgórze → Strzyża PKM", "/trojmiasto/timetable/lines/l1?track=t1-out"],
            ["1 Strzyża PKM → Lawendowe Wzgórze", "/trojmiasto/timetable/lines/l1?track=t1-in"],
            // a line without a main track opens its page alone, which picks the track itself
            ["10", "/trojmiasto/timetable/lines/l10"],
            ["111 → Oliwa", "/trojmiasto/timetable/lines/l111?track=t111"],
        ]);
        expect(wrapper.findAll("section[aria-label]").map(section => section.attributes("aria-label"))).toEqual([
            "Linie",
            "Przystanki",
        ]);

        await found.findAll("a")[1].trigger("click");
        await flushPromises();

        expect(router.currentRoute.value.fullPath).toBe("/trojmiasto/timetable/lines/l1?track=t1-in");
    });

    it("shows the tracks without links until the line page exists", async () => {
        const { wrapper } = await mountSection();

        await type(wrapper, "1");

        const found = wrapper.find("section[aria-label=Linie]");
        expect(found.findAll("li").map(item => item.text())).toEqual([
            "1 Lawendowe Wzgórze → Strzyża PKM",
            "1 Strzyża PKM → Lawendowe Wzgórze",
            "10",
            "111 → Oliwa",
        ]);
        expect(found.findAll("a")).toHaveLength(0);
    });

    it("hides the lines section when no line matches", async () => {
        get.mockImplementation(async (endpoint: string) => ({
            data: endpoint === "v1_stop_group_list" ? groups : [],
        }));
        const { wrapper } = await mountSection();

        await type(wrapper, "Bra");

        expect(wrapper.find("section[aria-label=Przystanki]").exists()).toBe(true);
        expect(wrapper.find("section[aria-label=Linie]").exists()).toBe(false);
        expect(trackQueries()).toEqual([]);
    });

    it("keeps the found tracks while the next query is searched", async () => {
        const { wrapper } = await mountSection();

        await type(wrapper, "1");
        await wrapper.find("input").setValue("11");
        await flushPromises();

        expect(wrapper.findAll("section[aria-label=Linie] li").map(item => item.text())).toEqual([
            "1 Lawendowe Wzgórze → Strzyża PKM",
            "1 Strzyża PKM → Lawendowe Wzgórze",
            "10",
            "111 → Oliwa",
        ]);
    });

    it("lists the tracks of the first ten lines found only", async () => {
        const many = Array.from({ length: 12 }, (_, i) => line(`l${i + 1}`, `${i + 1}`));
        get.mockImplementation(async (endpoint: string) => ({
            data: endpoint === "v1_line_list" ? many : endpoint === "v1_track_list" ? [] : groups,
        }));
        const { wrapper } = await mountSection();

        await type(wrapper, "1");

        expect(trackQueries()).toEqual([{ line: "l1,l2,l3,l4,l5,l6,l7,l8,l9,l10", limit: 100 }]);
        expect(wrapper.findAll("section[aria-label=Linie] li")).toHaveLength(10);
    });

    it("drops a late answer to an older line query", async () => {
        let answerOld: (value: unknown) => void = () => {};
        get.mockImplementation(async (endpoint: string, options: { query?: { symbol?: string } }) => {
            if (endpoint !== "v1_line_list") {
                return { data: [] };
            }

            if (options.query?.symbol === "1") {
                return new Promise(resolve => (answerOld = resolve));
            }

            return { data: [line("l11", "11")] };
        });
        const { wrapper } = await mountSection();

        await type(wrapper, "1");
        await type(wrapper, "11");
        answerOld({ data: lines });
        await flushPromises();

        expect(wrapper.findAll("section[aria-label=Linie] li").map(item => item.text())).toEqual(["11"]);
    });
});
