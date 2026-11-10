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

async function mountSection(path = "/trojmiasto/timetable", recent: ReturnType<typeof stop>[] = []) {
    const pinia = createPinia();
    setActivePinia(pinia);
    for (const chosen of [...recent].reverse()) {
        useHistoryStore().push({ stop: chosen, date: Temporal.Instant.from("2026-11-10T08:00:00Z") });
    }

    const router = createRouter({ history: createMemoryHistory(), routes });
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

const stopGroupQueries = () =>
    get.mock.calls.filter(([endpoint]) => endpoint === "v1_stop_group_list").map(([, options]) => options.query);

describe("TimetablePage's search", () => {
    beforeEach(() => {
        vi.useFakeTimers();
        get.mockReset();
        get.mockImplementation(async (endpoint: string) => {
            switch (endpoint) {
                case "v1_stop_group_list":
                    return { data: groups };
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

    it("asks to pick a stop or a line while none is open", async () => {
        const { wrapper, router } = await mountSection();

        expect(wrapper.text()).toContain("Wybierz przystanek albo linię");

        await router.push("/trojmiasto/timetable/stops/s1");
        await flushPromises();

        expect(wrapper.text()).not.toContain("Wybierz przystanek albo linię");
    });
});
