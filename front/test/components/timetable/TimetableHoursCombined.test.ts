// @vitest-environment happy-dom
import { describe, expect, it } from "vitest";
import { mount } from "@vue/test-utils";
import TimetableHoursCombined from "@/components/timetable/TimetableHoursCombined.vue";
import { buildCombinedTimetable } from "@/timetable";
import { date, liveDay, timeZone } from "@/components/timetable/timetable.fixtures";

const global = {
    stubs: {
        LineSymbol: { props: ["line"], template: "<span class='symbol'>{{ line.symbol }}</span>" },
        UiTooltip: { template: "<span class='tooltip'><slot /></span>" },
    },
};

const mountTable = (nearest?: string) =>
    mount(TimetableHoursCombined, {
        props: { rows: buildCombinedTimetable(liveDay, date, timeZone), nearest },
        global,
    });

const row = (wrapper: ReturnType<typeof mountTable>, hour: string) =>
    wrapper.findAll(".row").find(row => row.find(".hour").text() === hour);

describe("TimetableHoursCombined", () => {
    it("lists all lines' minutes in rows of hours, each with its line and its headsign on tap", () => {
        const five = row(mountTable(), "05").findAll(".minute");

        expect(five.map(minute => `${minute.find(".digits").text()} ${minute.find(".symbol").text()}`)).toEqual([
            "10 6",
            "10 8",
            "25 6",
            "30 8",
            "32 111",
            "40 6",
            "50 8",
            "55 6",
        ]);
        expect(five[2].find(".tooltip").text()).toBe("Siedlce");
        expect(five[2].attributes("aria-label")).toBe("05:25, linia 6, kierunek Siedlce");
    });

    it("dims departed minutes", () => {
        const wrapper = mountTable();

        expect(
            row(wrapper, "09")
                .findAll(".minute")
                .map(minute => minute.classes("departed")),
        ).toEqual([true, true, true, true, true, true, false, false, false]);
    });

    it("marks the run to scroll to", () => {
        const wrapper = mountTable("l6-2026-11-13-09:40");

        const marked = wrapper.findAll("[data-nearest]");
        expect(marked.map(minute => `${minute.find(".digits").text()} ${minute.find(".symbol").text()}`)).toEqual([
            "40 6",
        ]);
    });
});
