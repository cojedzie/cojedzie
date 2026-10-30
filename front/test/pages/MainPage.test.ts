// @vitest-environment happy-dom
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { flushPromises, mount } from "@vue/test-utils";
import { createPinia, setActivePinia } from "pinia";
import { createMemoryHistory, createRouter } from "vue-router";
import MainPage from "@/pages/MainPage.vue";
import { useDeparturesStore } from "@/stores/departures";
import { useMessagesStore } from "@/stores/messages";
import { useSelectionStore } from "@/stores/selection";
import { useDeparturesSettingsStore } from "@/stores/departures-settings";
import { useMessagesSettingsStore } from "@/stores/messages-settings";

const get = vi.hoisted(() => vi.fn());
vi.mock("@/api", () => ({ default: { get } }));

const stop = (id: string) => ({ id, name: `Stop ${id}`, group: "Group", $type: "vnd.cojedzie.stop" }) as any;

const stubs = {
    teleport: true,
    UiIcon: true,
    UiTooltip: true,
    UiDialog: true,
    UiFold: { template: "<div><slot /></div>" },
    MessagesList: true,
    DeparturesList: { template: '<div class="departures-list" />' },
    SettingsMessages: true,
    SettingsDepartures: true,
    FavouritesAdder: true,
    FavouritesList: { template: '<div class="favourites-list" />' },
    StopPicker: { emits: ["select"], template: "<button class=\"pick\" @click=\"$emit('select', [{ id: '5' }])\" />" },
    StopPickerEntry: {
        props: ["stop"],
        template: '<div class="entry">{{ stop.id }}<slot name="primary-action" /></div>',
    },
};

async function mountPage({ stops = [] as any[], departuresSettings = {}, messagesSettings = {} } = {}) {
    const pinia = createPinia();
    setActivePinia(pinia);
    useDeparturesSettingsStore().update(departuresSettings);
    useMessagesSettingsStore().update(messagesSettings);

    const router = createRouter({
        history: createMemoryHistory(),
        routes: [{ path: "/:provider", component: { template: "<div />" } }],
    });
    router.push("/trojmiasto");
    await router.isReady();

    const selection = useSelectionStore();
    selection.replace(stops);

    const departures = useDeparturesStore();
    const messages = useMessagesStore();
    vi.spyOn(departures, "update").mockResolvedValue();
    vi.spyOn(messages, "update").mockResolvedValue();

    const wrapper = mount(MainPage, { global: { plugins: [pinia, router], stubs } });
    await flushPromises();

    return { wrapper, selection, departures, messages };
}

describe("MainPage", () => {
    beforeEach(() => {
        vi.useFakeTimers();
        get.mockReset();
        get.mockResolvedValue({ data: { id: "trojmiasto", name: "Trójmiasto", attribution: "ZTM" } });
        document.head.innerHTML = '<link rel="manifest" href="/manifest.json">';
    });

    afterEach(() => {
        vi.useRealTimers();
        vi.restoreAllMocks();
    });

    it("loads the provider from the route", async () => {
        const { wrapper, selection, messages } = await mountPage();

        expect(get).toHaveBeenCalledWith("v1_provider_details", {
            params: { provider: "trojmiasto" },
            version: "^1.0",
        });
        expect(selection.provider.id).toBe("trojmiasto");
        expect(messages.update).toHaveBeenCalled();
        expect(wrapper.find(".attribution__attribution").text()).toBe("ZTM");
        expect(document.querySelector<HTMLLinkElement>('link[rel="manifest"]').href).toContain(
            "/trojmiasto/manifest.json",
        );
    });

    it("asks to pick stops when none are selected", async () => {
        const { wrapper } = await mountPage();

        expect(wrapper.find(".departures-list").exists()).toBe(false);
        expect(wrapper.find(".alert-info").exists()).toBe(true);
    });

    it("adds the selected stop and refreshes the departures", async () => {
        const { wrapper, selection, departures } = await mountPage();
        vi.mocked(departures.update).mockClear();

        await wrapper.find(".pick").trigger("click");
        await flushPromises();

        expect(selection.stops.map(s => s.id)).toEqual(["5"]);
        expect(departures.update).toHaveBeenCalledTimes(1);
        expect(wrapper.find(".departures-list").exists()).toBe(true);
    });

    it("removes a stop and clears them all", async () => {
        const { wrapper, selection } = await mountPage({ stops: [stop("1"), stop("2"), stop("3")] });

        await wrapper.findAll(".entry button")[1].trigger("click");
        expect(selection.stops.map(s => s.id)).toEqual(["1", "3"]);

        await wrapper.find(".picker .section__title button").trigger("click");
        expect(selection.stops).toEqual([]);
    });

    it("refreshes the departures and messages on the configured interval", async () => {
        const { departures, messages } = await mountPage({
            stops: [stop("1")],
            departuresSettings: { autorefresh: true, autorefreshInterval: 10 },
            messagesSettings: { autorefresh: true, autorefreshInterval: 30 },
        });
        vi.mocked(departures.update).mockClear();
        vi.mocked(messages.update).mockClear();

        await vi.advanceTimersByTimeAsync(30_000);

        expect(departures.update).toHaveBeenCalledTimes(3);
        expect(messages.update).toHaveBeenCalledTimes(1);
    });

    it("restarts the refresh when the settings change", async () => {
        const { departures } = await mountPage({
            stops: [stop("1")],
            departuresSettings: { autorefresh: true, autorefreshInterval: 10 },
        });
        const settings = useDeparturesSettingsStore();
        vi.mocked(departures.update).mockClear();

        settings.update({ autorefreshInterval: 20 });
        await vi.advanceTimersByTimeAsync(10_000);
        expect(departures.update).not.toHaveBeenCalled();

        await vi.advanceTimersByTimeAsync(10_000);
        expect(departures.update).toHaveBeenCalledTimes(1);

        settings.update({ autorefresh: false });
        await vi.advanceTimersByTimeAsync(60_000);
        expect(departures.update).toHaveBeenCalledTimes(1);
    });

    it("stops refreshing once unmounted", async () => {
        const { wrapper, departures } = await mountPage({ stops: [stop("1")] });
        vi.mocked(departures.update).mockClear();

        wrapper.unmount();
        await vi.advanceTimersByTimeAsync(60_000);

        expect(departures.update).not.toHaveBeenCalled();
    });

    it("switches the picker between search and favourites", async () => {
        const { wrapper } = await mountPage();

        expect(wrapper.find(".pick").exists()).toBe(true);

        await wrapper.findAll(".picker .section__title button").at(-1).trigger("click");

        expect(wrapper.find(".pick").exists()).toBe(false);
        expect(wrapper.find(".favourites-list").exists()).toBe(true);
    });
});
