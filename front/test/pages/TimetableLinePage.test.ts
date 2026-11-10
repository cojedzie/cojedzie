// @vitest-environment happy-dom
import { beforeEach, describe, expect, it, vi } from "vitest";
import { defineComponent } from "vue";
import { flushPromises, mount } from "@vue/test-utils";
import { createPinia, setActivePinia } from "pinia";
import { createMemoryHistory, createRouter, type RouteRecordRaw } from "vue-router";
import TimetablePage from "@/pages/TimetablePage.vue";
import TimetableLinePage from "@/pages/TimetableLinePage.vue";

const get = vi.hoisted(() => vi.fn());
vi.mock("@/api", () => ({ default: { get } }));

const line = (id: string, symbol: string) => ({ id, symbol, type: "tram", night: false, fast: false });
const six = line("l6", "6");
const eight = line("l8", "8");
const empty = line("l0", "0");

const stop = (id: string, name: string) => ({ id, name });
const lostowice = stop("a", "Łostowice");
const brama = stop("s1", "Brama Wyżynna");
const wrzeszcz = stop("b", "Wrzeszcz");
const jelitkowo = stop("c", "Jelitkowo");

const track = (id: string, kind: string, line: object, stops: object[]) => ({
    id,
    kind,
    description: "",
    line,
    stops,
    destination: stops.at(-1),
});

// in the API's order: a side track first, the main ones in both directions after it
const tracks: Record<string, object[]> = {
    l6: [
        track("t3", "side", six, [lostowice, brama, wrzeszcz]),
        track("t2", "main", six, [jelitkowo, wrzeszcz, lostowice]),
        track("t1", "main", six, [lostowice, brama, wrzeszcz, jelitkowo]),
    ],
    l8: [track("t8", "main", eight, [wrzeszcz, jelitkowo])],
    l0: [],
};

const lines: Record<string, object> = { l6: six, l8: eight, l0: empty };

// renders the field and every option, letting the test pick one
const UiSelect = defineComponent({
    props: ["options", "modelValue"],
    emits: ["update:modelValue"],
    template: `<div>
        <div class="field"><slot name="selected" :option="modelValue" /></div>
        <button v-for="o in options" :key="o.id" class="option" @click="$emit('update:modelValue', o)">
            <slot :option="o" />
        </button>
    </div>`,
});

const stubs = {
    UiIcon: true,
    UiTooltip: true,
    UiSelect,
    LineSymbol: { props: ["line"], template: "<span class='symbol'>{{ line.symbol }}</span>" },
    StopLabel: { props: ["stop"], template: "<span>{{ stop.name }}</span>" },
};

const stopPage = { template: "<p class='stop-page'>{{ $route.params.stop }}</p>" };
const routes: RouteRecordRaw[] = [
    {
        path: "/:provider/timetable",
        name: "timetable",
        component: TimetablePage,
        children: [
            { path: "stops/:stop", name: "timetable-stop", component: stopPage },
            { path: "lines/:line", name: "timetable-line", components: { side: TimetableLinePage } },
            {
                path: "lines/:line/stops/:stop",
                name: "timetable-line-stop",
                components: { default: stopPage, side: TimetableLinePage },
            },
        ],
    },
];

async function mountSection(path: string) {
    const pinia = createPinia();
    setActivePinia(pinia);

    const router = createRouter({ history: createMemoryHistory(), routes });
    await router.push(path);
    await router.isReady();

    const wrapper = mount({ template: "<router-view />" }, { global: { plugins: [pinia, router], stubs } });
    await flushPromises();

    return { wrapper, router };
}

const notFound = () => Promise.reject({ response: { status: 404 } });

function answer(endpoint: string, options: { params?: { line?: string }; query?: { line?: string } }) {
    switch (endpoint) {
        case "v1_provider_details":
            return Promise.resolve({ data: { id: "trojmiasto", timezone: "Europe/Warsaw" } });
        case "v1_line_details": {
            const found = lines[options.params?.line ?? ""];
            return found ? Promise.resolve({ data: found }) : notFound();
        }
        case "v1_track_list":
            return Promise.resolve({ data: tracks[options.query?.line ?? ""] ?? [] });
        default:
            return Promise.resolve({ data: [] });
    }
}

const requests = (endpoint: string) => get.mock.calls.filter(([called]) => called === endpoint);

type Wrapper = Awaited<ReturnType<typeof mountSection>>["wrapper"];

const field = (wrapper: Wrapper) => wrapper.find(".field").text();
const options = (wrapper: Wrapper) => wrapper.findAll(".option").map(option => option.text());
const rail = (wrapper: Wrapper) => wrapper.find("ol");
const railStops = (wrapper: Wrapper) =>
    rail(wrapper)
        .findAll("li")
        .map(item => item.text());
const railLinks = (wrapper: Wrapper) =>
    rail(wrapper)
        .findAll("a")
        .map(link => link.attributes("href"));

describe("the line page", () => {
    beforeEach(() => {
        get.mockReset();
        get.mockImplementation(answer);
    });

    it("heads the page with the line and offers its tracks, the main ones first", async () => {
        const { wrapper } = await mountSection("/trojmiasto/timetable/lines/l6");

        expect(wrapper.find("h2").text()).toBe("Linia 6");
        expect(options(wrapper)).toEqual(["→ Łostowice", "→ Jelitkowo", "→ Wrzeszcz skrócony / inny przebieg"]);
        expect(requests("v1_line_details")).toHaveLength(1);
        expect(requests("v1_track_list")).toHaveLength(1);
    });

    it("shows the first main track's stops, each but the last opening the line's timetable there", async () => {
        const { wrapper } = await mountSection("/trojmiasto/timetable/lines/l6");

        expect(field(wrapper)).toBe("Kierunek: Łostowice");
        expect(railStops(wrapper)).toEqual(["Jelitkowo", "Wrzeszcz", "Łostowice przystanek końcowy"]);
        expect(railLinks(wrapper)).toEqual([
            "/trojmiasto/timetable/lines/l6/stops/c?track=t2",
            "/trojmiasto/timetable/lines/l6/stops/b?track=t2",
        ]);
    });

    it("shows the track the URL asks for, and the first main one for a track the line doesn't have", async () => {
        const asked = await mountSection("/trojmiasto/timetable/lines/l6?track=t1");
        expect(field(asked.wrapper)).toBe("Kierunek: Jelitkowo");
        expect(railStops(asked.wrapper)[0]).toBe("Łostowice");

        const unknown = await mountSection("/trojmiasto/timetable/lines/l6?track=gone");
        expect(field(unknown.wrapper)).toBe("Kierunek: Łostowice");
        expect(unknown.router.currentRoute.value.fullPath).toBe("/trojmiasto/timetable/lines/l6?track=gone");
    });

    it("puts the track picked in the URL without adding to the history", async () => {
        const { wrapper, router } = await mountSection("/trojmiasto/timetable");
        await router.push("/trojmiasto/timetable/lines/l6");
        await flushPromises();

        await wrapper.findAll(".option")[1].trigger("click");
        await flushPromises();

        expect(router.currentRoute.value.query).toEqual({ track: "t1" });
        expect(field(wrapper)).toBe("Kierunek: Jelitkowo");

        router.back();
        await flushPromises();

        expect(router.currentRoute.value.fullPath).toBe("/trojmiasto/timetable");
    });

    it("stays next to a stop opened on the route, marking it current", async () => {
        const { wrapper, router } = await mountSection("/trojmiasto/timetable/lines/l6");

        await rail(wrapper).findAll("a")[1].trigger("click");
        await flushPromises();

        expect(router.currentRoute.value.fullPath).toBe("/trojmiasto/timetable/lines/l6/stops/b?track=t2");
        expect(wrapper.find(".stop-page").text()).toBe("b");
        expect(requests("v1_line_details")).toHaveLength(1);
        expect(
            rail(wrapper)
                .findAll("a")
                .map(link => link.attributes("aria-current")),
        ).toEqual([undefined, "page"]);
    });

    it("shows a track through the open stop when the URL names none", async () => {
        const { wrapper } = await mountSection("/trojmiasto/timetable/lines/l6/stops/s1");

        expect(field(wrapper)).toBe("Kierunek: Jelitkowo");
        expect(rail(wrapper).find("a[aria-current=page]").attributes("href")).toBe(
            "/trojmiasto/timetable/lines/l6/stops/s1?track=t1",
        );
    });

    it("asks to pick a stop on the line's route while none is open, on a stop's timetable the search stays", async () => {
        const { wrapper, router } = await mountSection("/trojmiasto/timetable/lines/l6");

        expect(wrapper.text()).toContain("Wybierz przystanek na trasie linii 6");

        await router.push("/trojmiasto/timetable/stops/s1");
        await flushPromises();

        expect(wrapper.find("h2").text()).toBe("Rozkłady jazdy");
        expect(wrapper.find(".stop-page").text()).toBe("s1");
    });

    it("says when the line doesn't exist", async () => {
        const { wrapper } = await mountSection("/trojmiasto/timetable/lines/gone");

        expect(wrapper.text()).toContain("Nie znaleziono linii");
        expect(wrapper.find("ol").exists()).toBe(false);
    });

    it("says when the line has no tracks", async () => {
        const { wrapper } = await mountSection("/trojmiasto/timetable/lines/l0");

        expect(wrapper.find("h2").text()).toBe("Linia 0");
        expect(wrapper.text()).toContain("Ta linia nie ma żadnej trasy");
        expect(wrapper.find("ol").exists()).toBe(false);
    });

    it("is busy until the line arrives, and drops a late answer for the previous line", async () => {
        let answerSix: () => void = () => {};
        get.mockImplementation((endpoint: string, options: { params?: { line?: string } }) =>
            endpoint === "v1_line_details" && options.params?.line === "l6"
                ? new Promise(resolve => (answerSix = () => resolve({ data: six })))
                : answer(endpoint, options),
        );
        const { wrapper, router } = await mountSection("/trojmiasto/timetable/lines/l6");

        expect(wrapper.find("[aria-busy=true]").exists()).toBe(true);

        await router.push("/trojmiasto/timetable/lines/l8");
        await flushPromises();
        answerSix();
        await flushPromises();

        expect(wrapper.find("h2").text()).toBe("Linia 8");
        expect(railStops(wrapper)).toEqual(["Wrzeszcz", "Jelitkowo przystanek końcowy"]);
        expect(wrapper.find("[aria-busy=true]").exists()).toBe(false);
    });
});
