// @vitest-environment happy-dom
import { describe, expect, it } from "vitest";
import { mount } from "@vue/test-utils";
import UiDatePicker from "@/components/ui/UiDatePicker.vue";
import UiCalendar from "@/components/ui/UiCalendar.vue";
import { Temporal } from "@/time";

const global = {
    stubs: { UiIcon: true, teleport: true, UiDialog: { template: "<div class='dialog'><slot /></div>" } },
};

const day = (date: string) => Temporal.PlainDate.from(date);

function picker(modelValue: string) {
    const wrapper = mount(UiDatePicker, {
        props: {
            modelValue: day(modelValue),
            today: day("2026-11-09"),
            min: day("2026-10-26"),
            max: day("2026-11-22"),
            "onUpdate:modelValue": (value: Temporal.PlainDate) => wrapper.setProps({ modelValue: value }),
        },
        global,
    });

    return wrapper;
}

const picked = (wrapper: ReturnType<typeof picker>) =>
    (wrapper.emitted("update:modelValue") as [Temporal.PlainDate][] | undefined)?.map(([date]) => date.toString());

const [previous, next] = [0, 1].map(index => (wrapper: ReturnType<typeof picker>) => wrapper.findAll(".step")[index]);

describe("UiDatePicker", () => {
    it("names the chosen day and steps a day with the arrows", async () => {
        const wrapper = picker("2026-11-13");

        expect(wrapper.find(".name:not(.inactive)").text()).toBe("piątek, 13.11.2026");

        await next(wrapper).trigger("click");
        await previous(wrapper).trigger("click");
        await previous(wrapper).trigger("click");

        expect(picked(wrapper)).toEqual(["2026-11-14", "2026-11-13", "2026-11-12"]);
    });

    it("disables the arrow at each end of the range", () => {
        expect(previous(picker("2026-10-26")).attributes("disabled")).toBe("");
        expect(next(picker("2026-11-22")).attributes("disabled")).toBe("");
    });

    it("goes back to today with a button disabled on today", async () => {
        const wrapper = picker("2026-11-13");
        const button = wrapper.find(".today");

        await button.trigger("click");

        expect(picked(wrapper)).toEqual(["2026-11-09"]);
        expect(button.attributes("disabled")).toBe("");
    });

    it("leaves the today button out when asked to", () => {
        const wrapper = mount(UiDatePicker, { props: { modelValue: day("2026-11-13"), todayButton: false }, global });

        expect(wrapper.find(".today").exists()).toBe(false);
    });

    it("opens the calendar from the field and closes it with the day picked", async () => {
        const wrapper = picker("2026-11-13");
        const field = wrapper.find(".field");

        await field.trigger("click");
        expect(field.attributes("aria-expanded")).toBe("true");

        await wrapper.find("[data-date='2026-11-20']").trigger("click");

        expect(picked(wrapper)).toEqual(["2026-11-20"]);
        expect(wrapper.findComponent(UiCalendar).exists()).toBe(false);
        expect(field.attributes("aria-expanded")).toBe("false");
    });

    it("closes the calendar with Escape", async () => {
        const wrapper = picker("2026-11-13");

        await wrapper.find(".field").trigger("click");
        await wrapper.find("table").trigger("keydown", { key: "Escape" });

        expect(wrapper.findComponent(UiCalendar).exists()).toBe(false);
        expect(picked(wrapper)).toBeUndefined();
    });
});
