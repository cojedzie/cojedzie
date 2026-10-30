// @vitest-environment happy-dom
import { describe, expect, it } from "vitest";
import { defineComponent } from "vue";
import { mount } from "@vue/test-utils";
import StopPickerEntry from "@/components/stop-picker/StopPickerEntry.vue";
import filters from "@/filters";

const line = (id: string, symbol: string, type: string, flags: { night?: boolean; fast?: boolean } = {}) => ({
    id,
    symbol,
    type,
    night: false,
    fast: false,
    ...flags,
    $type: "vnd.cojedzie.line",
});

const destination = (id: string, name: string, lines) => ({
    stop: { id, name, $type: "vnd.cojedzie.stop" },
    lines,
});

const stop = { id: "1", name: "Brama Wyżynna", group: "Brama Wyżynna", $type: "vnd.cojedzie.stop" };

const stub = (name: string, template = "<div><slot /></div>") =>
    defineComponent({ name, props: ["stop", "line", "simple"], template });

function mountEntry(props = {}, slots = {}) {
    return mount(StopPickerEntry, {
        props: { stop, ...props },
        slots,
        global: {
            plugins: [filters],
            stubs: {
                teleport: true,
                StopLabel: stub("StopLabel", '<span class="label">{{ stop.name }}</span>'),
                LineSymbol: stub(
                    "LineSymbol",
                    `<span class="symbol" :class="{ night: line.night, fast: line.fast }">{{ line.symbol }}</span>`,
                ),
                StopDetailsDialog: stub("StopDetailsDialog"),
                StopMap: stub("StopMap"),
                UiDialog: stub("UiDialog"),
                UiIcon: true,
                UiTooltip: true,
            },
        },
    });
}

describe("StopPickerEntry", () => {
    it("renders the stop and the primary action slot", () => {
        const wrapper = mountEntry({}, { "primary-action": '<button class="custom-action">pick</button>' });

        expect(wrapper.find(".label").text()).toBe("Brama Wyżynna");
        expect(wrapper.find(".custom-action").exists()).toBe(true);
    });

    it("lists each destination once with its lines grouped by type", () => {
        const wrapper = mountEntry({
            stop: {
                ...stop,
                destinations: [
                    destination("2", "Oliwa", [
                        line("a", "6", "tram"),
                        line("b", "8", "tram"),
                        line("c", "N1", "bus", { night: true }),
                    ]),
                    destination("3", "Oliwa", [line("d", "2", "tram")]),
                    destination("4", "Wrzeszcz", [
                        line("e", "110", "bus", { fast: true }),
                        line("f", "111", "bus", { fast: true }),
                        line("g", "112", "bus", { fast: true }),
                        line("h", "9", "tram"),
                    ]),
                ],
            },
        });

        const destinations = wrapper.findAll(".destination");
        expect(destinations.map(destination => destination.find(".destination__name").text())).toEqual([
            "Oliwa",
            "Wrzeszcz",
        ]);

        const symbols = destination =>
            destination.findAll(".symbol").map(symbol => ({
                symbol: symbol.text(),
                night: symbol.classes("night"),
                fast: symbol.classes("fast"),
            }));

        expect(symbols(destinations[0])).toEqual([
            { symbol: "6, 8", night: false, fast: false },
            { symbol: "N1", night: true, fast: false },
        ]);
        expect(symbols(destinations[1])).toEqual([
            { symbol: "110…", night: false, fast: true },
            { symbol: "9", night: false, fast: false },
        ]);
    });

    it("renders no destinations section without destinations", () => {
        const wrapper = mountEntry();

        expect(wrapper.find(".stop__destinations").exists()).toBe(false);
    });

    it("toggles the stop details with the info button", async () => {
        const wrapper = mountEntry();
        const info = wrapper.findAll(".stop__actions button")[0];

        expect(wrapper.findComponent({ name: "StopDetailsDialog" }).exists()).toBe(false);

        await info.trigger("click");
        expect(wrapper.findComponent({ name: "StopDetailsDialog" }).props("stop")).toEqual(stop);

        await info.trigger("click");
        expect(wrapper.findComponent({ name: "StopDetailsDialog" }).exists()).toBe(false);
    });

    it("closes the stop details when the dialog is left", async () => {
        const wrapper = mountEntry();

        await wrapper.findAll(".stop__actions button")[0].trigger("click");
        await wrapper.findComponent({ name: "StopDetailsDialog" }).vm.$emit("leave");

        expect(wrapper.findComponent({ name: "StopDetailsDialog" }).exists()).toBe(false);
    });

    it("shows the stop on a map while the map button is hovered", async () => {
        const wrapper = mountEntry();
        const map = wrapper.findAll(".stop__actions button")[1];
        const popup = () => wrapper.findComponent({ name: "UiDialog" });

        expect(popup().exists()).toBe(false);

        await map.trigger("mouseenter");
        expect(popup().exists()).toBe(true);
        expect(popup().findComponent({ name: "StopMap" }).props("stop")).toEqual(stop);

        await map.trigger("mouseleave");
        expect(popup().exists()).toBe(false);
    });

    it("keeps the map open while the map itself is hovered", async () => {
        const wrapper = mountEntry();
        const map = wrapper.findAll(".stop__actions button")[1];
        const popup = () => wrapper.findComponent({ name: "UiDialog" });

        await map.trigger("mouseenter");
        await popup().trigger("mouseenter");
        await map.trigger("mouseleave");
        expect(popup().exists()).toBe(true);

        await popup().trigger("mouseleave");
        expect(popup().exists()).toBe(false);
    });
});
