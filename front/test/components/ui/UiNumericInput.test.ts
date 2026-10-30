// @vitest-environment happy-dom
import { describe, expect, it } from "vitest";
import { mount } from "@vue/test-utils";
import UiNumericInput from "@/components/ui/UiNumericInput.vue";

const global = { stubs: { UiIcon: true } };

describe("UiNumericInput", () => {
    it("emits the parsed value on blur", async () => {
        const wrapper = mount(UiNumericInput, { props: { value: 5 }, global });

        await wrapper.find("input").setValue("12");
        await wrapper.find("input").trigger("blur");

        expect(wrapper.emitted("update:value")).toEqual([[12]]);
    });

    it("clamps typed values to min and max", async () => {
        const wrapper = mount(UiNumericInput, { props: { value: 5, min: 1, max: 10 }, global });
        const input = wrapper.find("input");

        await input.setValue("100");
        await input.trigger("blur");
        await input.setValue("-3");
        await input.trigger("blur");

        expect(wrapper.emitted("update:value")).toEqual([[10], [1]]);
    });

    it("increments and decrements by step, within the limits", async () => {
        const wrapper = mount(UiNumericInput, { props: { value: 9, step: 2, min: 0, max: 10 }, global });
        const [increment, decrement] = wrapper.findAll("button");

        await increment.trigger("click");
        await decrement.trigger("click");

        expect(wrapper.emitted("update:value")).toEqual([[10], [7]]);
    });

    it("disables the buttons at the limits", () => {
        const atMax = mount(UiNumericInput, { props: { value: 10, min: 0, max: 10 }, global });
        const atMin = mount(UiNumericInput, { props: { value: 0, min: 0, max: 10 }, global });

        expect(atMax.findAll("button").map(b => b.attributes("disabled") !== undefined)).toEqual([true, false]);
        expect(atMin.findAll("button").map(b => b.attributes("disabled") !== undefined)).toEqual([false, true]);
    });
});
