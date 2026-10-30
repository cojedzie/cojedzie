// @vitest-environment happy-dom
import { describe, expect, it } from "vitest";
import { mount } from "@vue/test-utils";
import { IconStar } from "@tabler/icons-vue";
import UiIcon from "@/components/ui/UiIcon.vue";

describe("UiIcon", () => {
    it("renders a stacked icon as layers", () => {
        const wrapper = mount(UiIcon, { props: { icon: "relative-time-limit" } });

        expect(wrapper.classes()).toEqual(expect.arrayContaining(["ui-icon", "ui-icon--stacked"]));
        expect(wrapper.findAll("svg")).toHaveLength(2);
    });

    it("renders a custom icon component", () => {
        const wrapper = mount(UiIcon, { props: { icon: IconStar } });

        expect(wrapper.classes()).toEqual(expect.arrayContaining(["ui-icon", "tabler-icon-star"]));
    });

    it("crops the padding of Tabler icons, but not of our own", () => {
        expect(mount(UiIcon, { props: { icon: "favourite" } }).attributes("viewBox")).toBe("2 2 20 20");
        expect(mount(UiIcon, { props: { icon: "line-bus" } }).attributes("viewBox")).toBe("0 0 512 512");
    });

    it("is fixed width when asked by the caller", () => {
        const wrapper = mount(UiIcon, { props: { icon: "favourite", fixedWidth: true } });

        expect(wrapper.classes()).toContain("ui-icon--fixed-width");
    });

    it("falls back to the unknown icon", () => {
        const wrapper = mount(UiIcon, { props: { icon: "nonexistent" as never } });

        expect(wrapper.classes()).toContain("tabler-icon-help-square-rounded");
    });
});
