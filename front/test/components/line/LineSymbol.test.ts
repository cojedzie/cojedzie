// @vitest-environment happy-dom
import { describe, expect, it } from "vitest";
import { mount } from "@vue/test-utils";
import LineSymbol from "@/components/line/LineSymbol.vue";

const line = { id: "1", symbol: "N1", type: "tram", night: false, fast: false };
const stubs = { UiIcon: { props: ["icon"], template: "<i class='ui-icon' :data-icon='icon' />" } };

const icons = (wrapper: ReturnType<typeof mount>) => wrapper.findAll(".ui-icon").map(i => i.attributes("data-icon"));

describe("LineSymbol", () => {
    it("marks night and fast lines", () => {
        const wrapper = mount(LineSymbol, { props: { line: { ...line, night: true, fast: true } }, global: { stubs } });

        expect(wrapper.classes()).toEqual(expect.arrayContaining(["line--night", "line--fast"]));
        expect(icons(wrapper)).toEqual(["line-tram", "night", "fast"]);
    });

    it("omits the type and night icons in the simple variant", () => {
        const wrapper = mount(LineSymbol, {
            props: { line: { ...line, night: true }, simple: true },
            global: { stubs },
        });

        expect(icons(wrapper)).toEqual([]);
        expect(wrapper.find(".badge").text()).toBe("N1");
    });
});
