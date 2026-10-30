// @vitest-environment happy-dom
import { beforeEach, describe, expect, it, vi } from "vitest";
import { defineComponent } from "vue";
import { mount } from "@vue/test-utils";

vi.stubGlobal(
    "ResizeObserver",
    class {
        observe = vi.fn();
        unobserve = vi.fn();
    },
);

vi.mock("@/components/map", () => ({
    LMap: defineComponent({
        name: "LMap",
        props: { options: Object, zoom: Number, center: Array, maxZoom: Number },
        emits: ["ready"],
        setup(_, { expose }) {
            expose({ root: document.createElement("div"), leafletObject: { id: "leaflet" } });
        },
        template: "<div class='l-map'><slot /></div>",
    }),
    LVectorLayer: defineComponent({
        name: "LVectorLayer",
        props: { url: String, attribution: String },
        template: "<div class='l-vector-layer' />",
    }),
}));

import UiMap from "@/components/ui/UiMap.vue";

beforeEach(() => {
    window.CoJedzie = { maptiler: { key: "secret" } } as typeof window.CoJedzie;
});

describe("UiMap", () => {
    it("passes its props to the map along with the default options", () => {
        const wrapper = mount(UiMap, { props: { zoom: 12, center: [54, 18], options: { dragging: false } } as never });
        const map = wrapper.findComponent({ name: "LMap" });

        expect(map.props()).toMatchObject({
            zoom: 12,
            center: [54, 18],
            options: { dragging: false },
        });
    });

    it("adds the vector layer authenticated with the configured key", () => {
        const wrapper = mount(UiMap);

        expect(wrapper.findComponent({ name: "LVectorLayer" }).props("url")).toContain("key=secret");
    });

    it("exposes the underlying Leaflet map", () => {
        const wrapper = mount(UiMap);

        expect(wrapper.vm.leafletObject).toEqual({ id: "leaflet" });
    });
});
