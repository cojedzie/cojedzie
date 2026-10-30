// @vitest-environment happy-dom
import { describe, expect, it } from "vitest";
import { mount } from "@vue/test-utils";
import UiSwitch from "@/components/ui/UiSwitch.vue";

describe("UiSwitch", () => {
    it("emits the negated value when clicked", async () => {
        const wrapper = mount(UiSwitch, { props: { value: false } });

        await wrapper.trigger("click");

        expect(wrapper.emitted("update:value")).toEqual([[true]]);
    });

    it("reflects the value and emits the checkbox state on change", async () => {
        const wrapper = mount(UiSwitch, { props: { value: true } });

        expect((wrapper.find("input").element as HTMLInputElement).checked).toBe(true);

        await wrapper.find("input").setValue(false);

        expect(wrapper.emitted("update:value")).toContainEqual([false]);
    });

    it("puts the attributes on the root element only", () => {
        const wrapper = mount(UiSwitch, { props: { value: false }, attrs: { "data-test": "x" } });

        expect(wrapper.attributes("data-test")).toBe("x");
        expect(wrapper.find("input").attributes("data-test")).toBeUndefined();
    });
});
