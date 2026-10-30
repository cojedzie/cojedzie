// @vitest-environment happy-dom
import { describe, expect, it } from "vitest";
import { defineComponent } from "vue";
import { mount } from "@vue/test-utils";
import StopPin from "@/components/stop/StopPin.vue";

const stop = {
    id: "1",
    name: "Brama Wyżynna",
    location: { lat: 54.3, lng: 18.6 },
    destinations: [{ stop: { id: "2" }, lines: [{ id: "l", type: "bus" }] }],
};

const UiMapPin = defineComponent({
    props: ["latLng", "variant"],
    template: "<div class='map-pin'><slot /></div>",
});
const StopLabel = defineComponent({ props: ["stop"], template: "<span class='label'>{{ stop.name }}</span>" });
const UiIcon = defineComponent({ props: ["icon"], template: "<i class='ui-icon' :data-icon='icon' />" });

const mountPin = (props: Record<string, unknown> = {}, slots: Record<string, string> = {}) =>
    mount(StopPin, { props: { stop, ...props }, slots, global: { stubs: { UiMapPin, StopLabel, UiIcon } } });

describe("StopPin", () => {
    it("places the pin at the stop location with the stop type", () => {
        const wrapper = mountPin();

        expect(wrapper.findComponent(UiMapPin).props("latLng")).toEqual(stop.location);
        expect(wrapper.classes()).toContain("ui-pin--bus");
        expect(wrapper.find(".ui-icon").attributes("data-icon")).toBe("stop");
    });

    it("prefers an explicit type", () => {
        const wrapper = mountPin({ type: "tram" });

        expect(wrapper.classes()).toContain("ui-pin--tram");
    });

    it("hides the label with no-label and allows replacing the icon", () => {
        const wrapper = mountPin({ noLabel: true }, { icon: "<b class='custom'>1</b>" });

        expect(wrapper.find(".label").exists()).toBe(false);
        expect(wrapper.find(".custom").text()).toBe("1");
        expect(wrapper.find(".ui-icon").exists()).toBe(false);
    });
});
