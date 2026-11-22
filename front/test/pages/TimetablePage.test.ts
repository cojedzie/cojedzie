// @vitest-environment happy-dom
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { flushPromises, mount } from "@vue/test-utils";
import { createPinia, setActivePinia } from "pinia";
import { defineComponent, onMounted, watch } from "vue";
import { createMemoryHistory, createRouter, type RouteRecordRaw, useRoute } from "vue-router";
import TimetablePage from "@/pages/TimetablePage.vue";
import { routes as appRoutes } from "@/routes";
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

// popups in place, their title and content
const UiDialog = {
    emits: ["leave"],
    template: "<div class='popup' v-bind='$attrs'><slot name='title' /><slot /></div>",
};

const stubs = {
    UiIcon: true,
    UiTooltip: true,
    UiDialog,
    teleport: true,
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

describe("TimetablePage's header", () => {
    /** Lets the stub pages emit what they loaded */
    let load: () => void;
    let loaded: Promise<void>;

    /** A page reporting what it loaded to the layout once the test lets it */
    const reporting = (event: string, data: object) =>
        defineComponent({
            emits: [event],
            setup(_, { emit }) {
                onMounted(async () => {
                    await loaded;
                    emit(event, data);
                });
                return () => null;
            },
        });

    const linePage = reporting("line", line("l8", "8", "tram"));

    const towards = (name: string, ...lines: object[]) => ({ stop: stop(`to-${name}`, name, "01"), lines });
    /** Where the stops' lines go from them */
    const destinationsOf: Record<string, object[]> = {
        s1: [
            towards("Jelitkowo", line("l8", "8", "tram"), line("l6", "6", "tram")),
            towards("Migowo", line("l2", "2", "tram")),
        ],
        s2: [towards("Łostowice", line("l8", "8", "tram"))],
    };

    /** The stop timetable, reporting each stop it opens once the test lets it */
    const stopPage = defineComponent({
        emits: ["stop"],
        setup(_, { emit }) {
            const route = useRoute();
            watch(
                () => route.params.stop as string,
                async id => {
                    await loaded;
                    const found = [bramaUp, bramaDown].find(stop => stop.id === id)!;
                    emit("stop", { ...found, destinations: destinationsOf[found.id] });
                },
                { immediate: true },
            );
            return () => null;
        },
    });

    /** The app's routes and their crumbs, with the layout and the stub pages */
    const section = appRoutes.find(route => route.name === "timetable")!;
    const routes = [
        {
            ...section,
            component: TimetablePage,
            children: section.children!.map(child => ({
                ...child,
                component: undefined,
                components: {
                    ...(child.name !== "timetable-line" && { default: stopPage }),
                    ...(child.name?.toString().startsWith("timetable-line") && { side: linePage }),
                },
            })),
        },
    ] as RouteRecordRaw[];

    beforeEach(() => {
        loaded = new Promise(resolve => (load = resolve));
        get.mockReset();
        get.mockResolvedValue({ data: { id: "trojmiasto", timezone: "Europe/Warsaw" } });
    });

    const crumbs = (wrapper: Awaited<ReturnType<typeof mountSection>>["wrapper"]) =>
        wrapper.findAll("nav[aria-label=Ścieżka] li").map(crumb => crumb.text());

    /** The stop the current crumb shows, or its text while it shows none */
    const currentCrumb = (wrapper: Awaited<ReturnType<typeof mountSection>>["wrapper"]) => {
        const current = wrapper.find("nav[aria-label=Ścieżka] [aria-current=page]");
        const label = current.findComponent({ name: "StopLabel" });
        return label.exists() ? label.props("stop").id : current.text();
    };

    it("keeps naming the open stop while the next one loads, instead of flashing a placeholder", async () => {
        const { wrapper, router } = await mountSection("/trojmiasto/timetable/stops/s1", [], routes);
        load();
        await flushPromises();
        expect(currentCrumb(wrapper)).toBe("s1");

        loaded = new Promise(resolve => (load = resolve));
        await router.push("/trojmiasto/timetable/stops/s2");
        await flushPromises();
        expect(currentCrumb(wrapper)).toBe("s1");

        load();
        await flushPromises();
        expect(currentCrumb(wrapper)).toBe("s2");
    });

    it("says what the crumb is when the next stop doesn't arrive within a second", async () => {
        vi.useFakeTimers();
        const { wrapper, router } = await mountSection("/trojmiasto/timetable/stops/s1", [], routes);
        load();
        await flushPromises();

        loaded = new Promise(resolve => (load = resolve));
        await router.push("/trojmiasto/timetable/stops/s2");
        await vi.advanceTimersByTimeAsync(1000);

        expect(currentCrumb(wrapper)).toBe("Przystanek");
        vi.useRealTimers();
    });

    it("names a deep-linked line and stop once their pages load them", async () => {
        const { wrapper } = await mountSection("/trojmiasto/timetable/lines/l8/stops/s1?track=t1", [], routes);

        expect(crumbs(wrapper)).toEqual(["Rozkład", "Linia", "Przystanek"]);

        load();
        await flushPromises();

        const nav = wrapper.find("nav[aria-label=Ścieżka]");
        const [root, onLine] = nav.findAll("a");
        expect([root, onLine].map(link => link.attributes("href"))).toEqual([
            "/trojmiasto/timetable",
            "/trojmiasto/timetable/lines/l8?track=t1",
        ]);
        // the line as the word and its symbol, named in full for screen readers; the stop with its code in a badge
        expect(onLine.attributes("aria-label")).toBe("Linia 8");
        expect(onLine.findComponent({ name: "LineSymbol" }).props("line")).toMatchObject({ id: "l8" });
        expect(onLine.text()).toMatch(/^Linia\s/);
        const current = nav.find("[aria-current=page]");
        expect(current.findComponent({ name: "StopLabel" }).props("stop")).toMatchObject({ id: "s1", variant: "01" });
    });

    type Wrapper = Awaited<ReturnType<typeof mountSection>>["wrapper"];

    /** The page's heading: the stop or line it names, or its text while it names none */
    const heading = (wrapper: Wrapper) => {
        const h1 = wrapper.find("h1");
        const stop = h1.findComponent({ name: "StopLabel" });
        const line = h1.findComponent({ name: "LineSymbol" });

        return [h1.text(), stop.exists() ? stop.props("stop").id : line.exists() ? line.props("line").id : null];
    };

    it("heads the search, a stop by its name, and a line with or without a stop by the line", async () => {
        load();
        const { wrapper, router } = await mountSection("/trojmiasto/timetable", [], routes);
        expect(heading(wrapper)).toEqual(["Rozkłady jazdy", null]);

        await router.push("/trojmiasto/timetable/stops/s1");
        await flushPromises();
        expect(heading(wrapper)).toEqual([expect.stringMatching(/^Rozkład na Brama Wyżynna/), "s1"]);

        await router.push("/trojmiasto/timetable/lines/l8");
        await flushPromises();
        expect(heading(wrapper)).toEqual([expect.stringMatching(/^Rozkład linii/), "l8"]);

        await router.push("/trojmiasto/timetable/lines/l8/stops/s1");
        await flushPromises();
        expect(heading(wrapper)).toEqual([expect.stringMatching(/^Rozkład linii/), "l8"]);
    });

    it("says what the page is until its line or stop loads", async () => {
        const onLine = await mountSection("/trojmiasto/timetable/lines/l8/stops/s1", [], routes);
        expect(heading(onLine.wrapper)).toEqual(["Rozkład linii", null]);

        const atStop = await mountSection("/trojmiasto/timetable/stops/s2", [], routes);
        expect(heading(atStop.wrapper)).toEqual(["Rozkład na przystanku", null]);
    });

    it("keeps heading the open stop while the next one loads", async () => {
        const { wrapper, router } = await mountSection("/trojmiasto/timetable/stops/s1", [], routes);
        load();
        await flushPromises();

        loaded = new Promise(resolve => (load = resolve));
        await router.push("/trojmiasto/timetable/stops/s2");
        await flushPromises();
        expect(heading(wrapper)[1]).toBe("s1");

        load();
        await flushPromises();
        expect(heading(wrapper)[1]).toBe("s2");
    });

    describe("line picker", () => {
        const picker = (wrapper: Wrapper) => wrapper.find("h1 button[aria-haspopup=dialog]");
        const rows = (wrapper: Wrapper) =>
            wrapper.findAll(".popup a").map(row => ({
                text: row.text().replace(/\s+/g, " "),
                href: row.attributes("href"),
                current: row.attributes("aria-current"),
            }));

        it("switches a line at a stop to another of the stop's lines, or to all of them, keeping the day", async () => {
            load();
            const { wrapper, router } = await mountSection(
                "/trojmiasto/timetable/lines/l8/stops/s1?track=t1&date=2026-11-13",
                [],
                routes,
            );

            expect(picker(wrapper).attributes("aria-label")).toBe("Linia 8, zmień linię");
            expect(picker(wrapper).attributes("aria-expanded")).toBe("false");

            await picker(wrapper).trigger("click");

            expect(picker(wrapper).attributes("aria-expanded")).toBe("true");
            expect(wrapper.find(".popup").text()).toContain("Linie na tym przystanku");
            expect(rows(wrapper)).toEqual([
                {
                    text: "2 Migowo",
                    href: "/trojmiasto/timetable/lines/l2/stops/s1?date=2026-11-13",
                    current: undefined,
                },
                {
                    text: "6 Jelitkowo",
                    href: "/trojmiasto/timetable/lines/l6/stops/s1?date=2026-11-13",
                    current: undefined,
                },
                {
                    text: "8 Jelitkowo",
                    href: "/trojmiasto/timetable/lines/l8/stops/s1?date=2026-11-13",
                    current: "page",
                },
                {
                    text: "Wszystkie linie razem",
                    href: "/trojmiasto/timetable/stops/s1?date=2026-11-13",
                    current: undefined,
                },
            ]);

            await wrapper.findAll(".popup a")[0].trigger("click");
            await flushPromises();

            expect(router.currentRoute.value.fullPath).toBe("/trojmiasto/timetable/lines/l2/stops/s1?date=2026-11-13");
            expect(wrapper.find(".popup").exists()).toBe(false);
        });

        it("is only on a line at a stop: a whole stop and a line's page have no stop's lines to pick", async () => {
            load();
            const atStop = await mountSection("/trojmiasto/timetable/stops/s1", [], routes);
            expect(picker(atStop.wrapper).exists()).toBe(false);

            const onLine = await mountSection("/trojmiasto/timetable/lines/l8", [], routes);
            expect(picker(onLine.wrapper).exists()).toBe(false);
        });
    });
});
