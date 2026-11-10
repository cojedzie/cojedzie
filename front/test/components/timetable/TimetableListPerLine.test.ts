// @vitest-environment happy-dom
import { describe, expect, it } from "vitest";
import { mount } from "@vue/test-utils";
import TimetableListPerLine from "@/components/timetable/TimetableListPerLine.vue";
import { buildLineLists } from "@/timetable";
import { date, liveDay, timeZone } from "@/components/timetable/timetable.fixtures";

const global = {
    stubs: { LineSymbol: { props: ["line"], template: "<span class='symbol'>{{ line.symbol }}</span>" } },
};

const mountList = (nearest?: string) =>
    mount(TimetableListPerLine, { props: { lists: buildLineLists(liveDay, date, timeZone), nearest }, global });

const cells = (wrapper: ReturnType<typeof mountList>, line: number) => wrapper.findAll(".line")[line].findAll(".cell");

describe("TimetableListPerLine", () => {
    it("heads each line's list with its symbol and main headsign, in line order", () => {
        const headings = mountList().findAll(".heading");

        expect(headings.map(heading => [heading.find(".symbol").text(), heading.find(".headsign").text()])).toEqual([
            ["6", "Łostowice"],
            ["8", "Jelitkowo"],
            ["111", "Oliwa PKP"],
            ["N1", "Dworzec Główny"],
        ]);
    });

    it("marks the runs to a line's other headsigns by letters, explained under its list", () => {
        const wrapper = mountList();
        const six = wrapper.findAll(".line")[0];

        expect(
            six
                .findAll(".cell")
                .slice(0, 3)
                .map(cell => cell.find(".note").exists() && cell.find(".note").text()),
        ).toEqual([false, false, "a"]);
        expect(six.findAll(".legend > span").map(item => item.text())).toEqual(["a — Siedlce"]);
        expect(wrapper.findAll(".line")[1].find(".legend").exists()).toBe(false);
    });

    it("dims departed runs and shows live runs with the estimate and the delay", () => {
        const wrapper = mountList();
        const six = cells(wrapper, 0);
        const live = six.find(cell => cell.text().startsWith("09:40"));

        expect(six[0].classes()).toContain("departed");
        expect(live.classes()).not.toContain("departed");
        expect(live.find(".superseded").text()).toBe("09:40");
        expect(live.find(".estimate b").text()).toBe("09:42");
        expect(live.find(".estimate .badge-warning").text()).toBe("+120s");
        expect(
            six
                .find(cell => cell.text().startsWith("09:55"))
                .find(".badge-danger")
                .text(),
        ).toBe("-40s");
    });

    it("marks the run to scroll to", () => {
        const wrapper = mountList("l6-2026-11-13-09:40");

        const marked = wrapper.findAll("[data-nearest]");
        expect(marked.map(cell => cell.find(".planned").text())).toEqual(["09:40"]);
    });
});
