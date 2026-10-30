// @vitest-environment happy-dom
import { describe, expect, it } from "vitest";
import { nextTick } from "vue";
import { mount } from "@vue/test-utils";
import { Lazy } from "@/components/utils";

const mountLazy = (activate: boolean) =>
    mount(Lazy, { props: { activate }, slots: { default: "<p class='content'>hello</p>" } });

describe("Lazy", () => {
    it("does not render the slot until activated", () => {
        expect(mountLazy(false).find(".content").exists()).toBe(false);
    });

    it("renders the slot once activated and keeps it afterwards", async () => {
        const wrapper = mountLazy(false);

        await wrapper.setProps({ activate: true });
        await nextTick();
        expect(wrapper.find(".content").exists()).toBe(true);

        await wrapper.setProps({ activate: false });
        await nextTick();
        expect(wrapper.find(".content").exists()).toBe(true);
    });
});
