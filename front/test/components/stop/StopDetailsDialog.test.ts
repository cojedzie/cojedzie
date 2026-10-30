// @vitest-environment happy-dom
import { beforeEach, describe, expect, it, vi } from "vitest";
import { defineComponent, ref } from "vue";
import { flushPromises, mount } from "@vue/test-utils";
import StopDetailsDialog from "@/components/stop/StopDetailsDialog.vue";

const line = (id: string, symbol: string) => ({ id, symbol, type: "tram" });
const stop = {
    id: "1",
    name: "Brama Wyżynna",
    location: { lat: 54.3, lng: 18.6 },
    destinations: [{ stop: { id: "2", name: "Oliwa", location: { lat: 54.4, lng: 18.5 } }, lines: [line("a", "6")] }],
};

const { useDataFromEndpoint, getTracksForDestination } = vi.hoisted(() => ({
    useDataFromEndpoint: vi.fn(),
    getTracksForDestination: vi.fn(),
}));

vi.mock("@vue-leaflet/vue-leaflet", async importOriginal => {
    const { defineComponent } = await import("vue");
    return { ...(await importOriginal<object>()), LFeatureGroup: defineComponent({ template: "<div><slot /></div>" }) };
});
vi.mock("@/composables/useDataFromEndpoint", () => ({ default: useDataFromEndpoint }));
vi.mock("@/composables/useService", () => ({ default: () => ({ getTracksForDestination }) }));

const stub = (name: string, template = "<div><slot /></div>") =>
    defineComponent({ name, props: ["stop", "line", "tracks", "modelValue", "type", "variant"], template });

describe("StopDetailsDialog", () => {
    beforeEach(() => {
        useDataFromEndpoint.mockReset();
        getTracksForDestination.mockReset();
        useDataFromEndpoint.mockReturnValue({
            data: ref([
                { track: { line: line("a", "6") } },
                { track: { line: line("b", "8") } },
                { track: { line: line("c", "6") } },
            ]),
            status: ref("success"),
        });
    });

    const mountDialog = () =>
        mount(StopDetailsDialog, {
            props: { stop },
            global: {
                stubs: {
                    UiDialog: stub("UiDialog"),
                    UiIcon: true,
                    UiMap: stub("UiMap"),
                    StopPin: stub("StopPin", "<i class='pin' />"),
                    StopLabel: stub("StopLabel", "<span class='label' />"),
                    LineSymbol: stub("LineSymbol", "<span class='line' />"),
                    TrackPicker: stub("TrackPicker", "<div class='picker' />"),
                },
            },
        });

    it("loads the tracks for the stop and shows each line once", () => {
        const wrapper = mountDialog();

        expect(useDataFromEndpoint).toHaveBeenCalledWith(
            "v1_stop_tracks",
            expect.objectContaining({ params: { stop: "1" } }),
        );
        expect(wrapper.find(".stop-details-dialog__lines").findAll(".line")).toHaveLength(2);
    });

    it("loads the tracks to the chosen destination and offers them for picking", async () => {
        getTracksForDestination.mockResolvedValue([{ id: "t1", line: line("a", "6"), stops: [] }]);
        const wrapper = mountDialog();

        expect(wrapper.find(".picker").exists()).toBe(false);

        await wrapper.find(".stop-details-dialog__actions button").trigger("click");
        await flushPromises();

        expect(getTracksForDestination).toHaveBeenCalledWith(stop, stop.destinations[0].stop);
        expect(wrapper.find(".picker").exists()).toBe(true);
    });
});
