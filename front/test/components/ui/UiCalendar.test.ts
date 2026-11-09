// @vitest-environment happy-dom
import { describe, expect, it } from "vitest";
import { mount } from "@vue/test-utils";
import UiCalendar from "@/components/ui/UiCalendar.vue";
import { Temporal } from "@/time";

const global = { stubs: { UiIcon: true } };
const day = (date: string) => Temporal.PlainDate.from(date);
const props = {
    modelValue: day("2026-11-13"),
    today: day("2026-11-09"),
    min: day("2026-10-26"),
    max: day("2026-11-22"),
};
const picked = (wrapper: ReturnType<typeof mount>) =>
    (wrapper.emitted("update:modelValue") as [Temporal.PlainDate][] | undefined)?.map(([date]) => date.toString());

const focusable = (wrapper: ReturnType<typeof mount>) => wrapper.find("[tabindex='0']").attributes("data-date");

describe("UiCalendar", () => {
    it("shows the chosen day's month with the chosen day, today and the days out of range", () => {
        const wrapper = mount(UiCalendar, { props, global });
        const cell = (date: string) => wrapper.find(`[data-date='${date}']`);

        expect(wrapper.find(".ui-calendar__month").text()).toBe("listopad 2026");
        expect(cell("2026-11-13").element.closest("td").getAttribute("aria-selected")).toBe("true");
        expect(cell("2026-11-09").attributes("aria-current")).toBe("date");
        expect([cell("2026-10-26"), cell("2026-11-22"), cell("2026-11-23")].map(d => d.attributes("disabled"))).toEqual(
            [undefined, undefined, ""],
        );
        expect(focusable(wrapper)).toBe("2026-11-13");
    });

    it("picks the day clicked", async () => {
        const wrapper = mount(UiCalendar, { props, global });

        await wrapper.find("[data-date='2026-11-20']").trigger("click");

        expect(picked(wrapper)).toEqual(["2026-11-20"]);
    });

    it("moves between days with the keyboard, within the range", async () => {
        const wrapper = mount(UiCalendar, { props, global });
        const grid = wrapper.find("table");

        await grid.trigger("keydown", { key: "ArrowRight" });
        expect(focusable(wrapper)).toBe("2026-11-14");

        await grid.trigger("keydown", { key: "ArrowUp" });
        expect(focusable(wrapper)).toBe("2026-11-07");

        await grid.trigger("keydown", { key: "Home" });
        expect(focusable(wrapper)).toBe("2026-11-02");

        // a month back is out of range, the first day is as far as it goes
        await grid.trigger("keydown", { key: "PageUp" });
        expect(focusable(wrapper)).toBe("2026-10-26");
        expect(wrapper.find(".ui-calendar__month").text()).toBe("październik 2026");
        expect(picked(wrapper)).toBeUndefined();
    });

    it("disables the month buttons at the ends of the range", () => {
        const wrapper = mount(UiCalendar, { props: { ...props, min: day("2026-11-01") }, global });

        expect(wrapper.findAll(".ui-calendar__nav").map(button => button.attributes("disabled"))).toEqual(["", ""]);
    });
});
