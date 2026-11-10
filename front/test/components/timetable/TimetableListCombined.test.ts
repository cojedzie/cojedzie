// @vitest-environment happy-dom
import { describe, expect, it } from "vitest";
import { mount } from "@vue/test-utils";
import TimetableListCombined from "@/components/timetable/TimetableListCombined.vue";
import { buildTimetableList } from "@/timetable";
import { date, liveDay, timeZone } from "@/components/timetable/timetable.fixtures";

const global = {
    stubs: {
        LineSymbol: { props: ["line"], template: "<span class='symbol'>{{ line.symbol }}</span>" },
        UiTooltip: { template: "<span class='tooltip'><slot /></span>" },
    },
};

const mountList = (nearest?: string) =>
    mount(TimetableListCombined, { props: { entries: buildTimetableList(liveDay, date, timeZone), nearest }, global });

describe("TimetableListCombined", () => {
    it("lists all lines' entries in departure order, each with its line, its headsign on tap", () => {
        const cells = mountList().findAll(".cell");
        const cell = (index: number) => [
            cells[index].find(".planned").text(),
            cells[index].find(".symbol").text(),
            cells[index].find(".tooltip").text(),
        ];

        // the night line's runs of the previous service day after midnight come first
        expect(cell(0)).toEqual(["00:20", "N1", "Dworzec Główny"]);
        expect(cell(2)).toEqual(["04:55", "6", "Łostowice"]);
        expect(cells[2].find("button").attributes("aria-label")).toBe("04:55, linia 6, kierunek Łostowice");
    });

    it("dims departed runs and shows live runs with the estimate and the delay", () => {
        const cells = mountList().findAll(".cell");
        const at = (time: string, symbol: string) =>
            cells.find(cell => cell.find(".planned").text() === time && cell.find(".symbol").text() === symbol);

        expect(at("09:25", "6").classes()).toContain("departed");
        expect(at("10:02", "111").classes()).not.toContain("departed");
        expect(at("10:02", "111").find(".estimate b").text()).toBe("10:07");
        expect(at("10:02", "111").find(".badge-warning").text()).toBe("+300s");
    });

    it("marks the run to scroll to", () => {
        const wrapper = mountList("l6-2026-11-13-09:40");

        const marked = wrapper.findAll("[data-nearest]");
        expect(marked.map(cell => `${cell.find(".planned").text()} ${cell.find(".symbol").text()}`)).toEqual([
            "09:40 6",
        ]);
    });
});
