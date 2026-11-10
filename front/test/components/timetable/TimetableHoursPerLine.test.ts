// @vitest-environment happy-dom
import { describe, expect, it } from "vitest";
import { mount } from "@vue/test-utils";
import TimetableHoursPerLine from "@/components/timetable/TimetableHoursPerLine.vue";
import { buildLineTimetables } from "@/timetable";
import { date, liveDay, timeZone } from "@/components/timetable/timetable.fixtures";

const global = {
    stubs: { LineSymbol: { props: ["line"], template: "<span class='symbol'>{{ line.symbol }}</span>" } },
};

const mountTables = () =>
    mount(TimetableHoursPerLine, { props: { tables: buildLineTimetables(liveDay, date, timeZone) }, global });

const rows = (wrapper: ReturnType<typeof mountTables>, line: number) =>
    wrapper
        .findAll(".line")
        [line].findAll(".row")
        .map(row => [row.find(".hour").text(), row.findAll(".minute").map(minute => minute.text())]);

describe("TimetableHoursPerLine", () => {
    it("heads each line's table with its symbol and main headsign", () => {
        const headings = mountTables().findAll(".heading");

        expect(headings.map(heading => heading.find(".headsign").text())).toEqual([
            "Łostowice",
            "Jelitkowo",
            "Oliwa PKP",
            "Dworzec Główny",
        ]);
    });

    it("lists a line's minutes in rows of hours, lettered by headsign when it has several", () => {
        const wrapper = mountTables();

        expect(rows(wrapper, 0).slice(0, 2)).toEqual([
            ["04", ["55a"]],
            ["05", ["10a", "25b", "40a", "55a"]],
        ]);
        expect(rows(wrapper, 1)[0]).toEqual(["05", ["10", "30", "50"]]);
    });

    it("dims departed minutes", () => {
        const wrapper = mountTables();
        const nine = wrapper.findAll(".line")[0].findAll(".row")[5].findAll(".minute");

        expect(nine.map(minute => minute.classes("departed"))).toEqual([true, true, false, false]);
    });
});
