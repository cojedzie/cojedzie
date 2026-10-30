// @vitest-environment happy-dom
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { flushPromises, mount } from "@vue/test-utils";
import { createPinia, setActivePinia } from "pinia";
import { createStore } from "vuex";
import StopPicker from "@/components/stop-picker/StopPicker.vue";
import { useHistoryStore } from "@/stores/history";

const get = vi.hoisted(() => vi.fn());
vi.mock("@/api", () => ({ default: { get } }));

const stop = (id: string) => ({ id, name: `Stop ${id}`, group: "Group", $type: "vnd.cojedzie.stop" }) as any;

const entryStub = {
    props: ["stop"],
    template: '<div class="entry"><slot name="primary-action" />{{ stop.name }}</div>',
};

function mountPicker(props = {}) {
    const pinia = createPinia();
    setActivePinia(pinia);
    const vuex = createStore({ state: { stops: [] }, mutations: { add: vi.fn() } });

    const wrapper = mount(StopPicker, {
        props,
        global: {
            plugins: [pinia, vuex],
            stubs: {
                FavouritesList: { template: '<div class="favourites" />' },
                StopPickerHistory: { template: '<div class="history" />' },
                StopPickerEntry: entryStub,
                UiIcon: true,
                UiTooltip: true,
            },
        },
    });

    return { wrapper, history: useHistoryStore() };
}

async function type(wrapper: ReturnType<typeof mountPicker>["wrapper"], value: string) {
    await wrapper.find("input").setValue(value);
    await vi.advanceTimersByTimeAsync(400);
    await flushPromises();
}

describe("StopPicker", () => {
    beforeEach(() => {
        vi.useFakeTimers();
        get.mockReset();
        get.mockResolvedValue({ data: [{ name: "Brama", stops: [stop("1"), stop("2")] }] });
    });

    afterEach(() => vi.useRealTimers());

    it("shows favourites and history when fewer than 3 characters are typed", async () => {
        const { wrapper } = mountPicker();

        await type(wrapper, "Br");

        expect(wrapper.find(".favourites").exists()).toBe(true);
        expect(wrapper.find(".history").exists()).toBe(true);
        expect(get).not.toHaveBeenCalled();
    });

    it("requests stop groups once 3 characters are typed and lists them", async () => {
        const { wrapper } = mountPicker();

        await type(wrapper, "Bra");

        expect(get).toHaveBeenCalledWith(
            "v1_stop_group_list",
            expect.objectContaining({ query: { name: "Bra", embed: "destinations" } }),
        );
        expect(wrapper.find(".stop-group__name").text()).toBe("Brama");
        expect(wrapper.findAll(".entry")).toHaveLength(2);
        expect(wrapper.find(".favourites").exists()).toBe(false);
    });

    it("hides blacklisted stops", async () => {
        const { wrapper } = mountPicker({ blacklist: [stop("1")] });

        await type(wrapper, "Bra");

        expect(wrapper.findAll(".entry").map(entry => entry.text())).toEqual(["Stop 2"]);
    });

    it("emits select and pushes history when a stop is selected", async () => {
        const { wrapper, history } = mountPicker();
        await type(wrapper, "Bra");

        await wrapper.findAll(".finder__stops li button")[0].trigger("click");

        expect(wrapper.emitted("select")).toEqual([[stop("1")]]);
        expect(history.all.map(entry => entry.stop.id)).toEqual(["1"]);
    });
});
