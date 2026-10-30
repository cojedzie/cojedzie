// @vitest-environment happy-dom
import { describe, expect, it } from "vitest";
import { nextTick } from "vue";
import { mount } from "@vue/test-utils";
import UiFold from "@/components/ui/UiFold.vue";
import Lazy from "@/components/utils/Lazy.vue";

const global = { components: { Lazy } };

describe("UiFold", () => {
    it("expands and folds when visibility changes", async () => {
        const wrapper = mount(UiFold, { props: { visible: false }, slots: { default: "content" }, global });

        await wrapper.setProps({ visible: true });
        expect(wrapper.classes()).toContain("fold--expanding");
        expect(wrapper.attributes("aria-expanded")).toBe("true");

        await wrapper.trigger("transitionend");
        expect(wrapper.classes()).toContain("fold--expanded");
        expect((wrapper.element as HTMLElement).style.height).toBe("auto");

        await wrapper.setProps({ visible: false });
        await new Promise(resolve => setTimeout(resolve));
        expect(wrapper.classes()).toContain("fold--folding");
        expect(wrapper.attributes("aria-expanded")).toBe("false");

        await wrapper.trigger("transitionend");
        expect(wrapper.classes()).toContain("fold--folded");
    });

    it("ignores transitions of its children", async () => {
        const wrapper = mount(UiFold, { props: { visible: false }, slots: { default: "<i class='child' />" }, global });

        await wrapper.setProps({ visible: true });
        await wrapper.find(".child").trigger("transitionend");

        expect(wrapper.classes()).toContain("fold--expanding");
    });

    it("renders lazy content only after the first expand", async () => {
        const wrapper = mount(UiFold, { props: { visible: false, lazy: true }, slots: { default: "content" }, global });
        expect(wrapper.text()).toBe("");

        await wrapper.setProps({ visible: true });
        await nextTick();
        expect(wrapper.text()).toBe("content");

        await wrapper.setProps({ visible: false });
        await nextTick();
        expect(wrapper.text()).toBe("content");
    });
});
