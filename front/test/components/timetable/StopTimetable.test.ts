// @vitest-environment happy-dom
import { afterEach, describe, expect, it, vi } from "vitest";
import { mount } from "@vue/test-utils";
import { nextTick } from "vue";
import StopTimetable from "@/components/timetable/StopTimetable.vue";
import { date, liveDay, now, timeZone } from "@/components/timetable/timetable.fixtures";

const view = (name: string) => ({
    props: ["lists", "entries", "tables", "rows", "nearest"],
    template: `<div class="${name}" :data-nearest="nearest" />`,
});
const UiToggleGroup = {
    props: ["modelValue", "options", "label"],
    emits: ["update:modelValue"],
    template: `<div class="toggle" :data-label="label">
        <button v-for="o in options" :key="o.value" :class="o.value" @click="$emit('update:modelValue', o.value)">{{ o.label }}</button>
    </div>`,
};

function mountTimetable(props: Record<string, unknown> = {}) {
    const wrapper = mount(StopTimetable, {
        props: {
            departures: liveDay,
            timeZone,
            date,
            "onUpdate:view": (value: string) => wrapper.setProps({ view: value }),
            "onUpdate:grouping": (value: string) => wrapper.setProps({ grouping: value }),
            ...props,
        },
        global: {
            stubs: {
                UiDatePicker: true,
                UiToggleGroup,
                TimetableListPerLine: view("list-per-line"),
                TimetableListCombined: view("list-combined"),
                TimetableHoursPerLine: view("hours-per-line"),
                TimetableHoursCombined: view("hours-combined"),
            },
        },
    });

    return wrapper;
}

const shown = (wrapper: ReturnType<typeof mountTimetable>) =>
    ["list-per-line", "list-combined", "hours-per-line", "hours-combined"].filter(name =>
        wrapper.find(`.${name}`).exists(),
    );

describe("StopTimetable", () => {
    afterEach(() => vi.restoreAllMocks());

    it("shows the list per line first, and the view and grouping picked with the switches", async () => {
        const wrapper = mountTimetable();
        expect(shown(wrapper)).toEqual(["list-per-line"]);

        await wrapper.find("[data-label=Grupowanie] .combined").trigger("click");
        expect(shown(wrapper)).toEqual(["list-combined"]);

        await wrapper.find("[data-label=Widok] .hours").trigger("click");
        expect(shown(wrapper)).toEqual(["hours-combined"]);

        await wrapper.find("[data-label=Grupowanie] .line").trigger("click");
        expect(shown(wrapper)).toEqual(["hours-per-line"]);
    });

    it("has no grouping to choose for one line, and shows it per line", () => {
        const wrapper = mountTimetable({ lineFixed: true, grouping: "combined" });

        expect(wrapper.find("[data-label=Grupowanie]").exists()).toBe(false);
        expect(shown(wrapper)).toEqual(["list-per-line"]);
    });

    it("says the day is loading until it has come", () => {
        const wrapper = mountTimetable({ departures: [], loading: true });

        expect(wrapper.find(".empty").text()).toBe("Wczytywanie rozkładu…");
        expect(shown(wrapper)).toEqual([]);
    });

    it("says when the day has no departures", () => {
        const wrapper = mountTimetable({ departures: [] });

        expect(wrapper.find(".empty").text()).toBe("Brak odjazdów w wybranym dniu");
        expect(shown(wrapper)).toEqual([]);
    });

    it("marks the first run not gone yet on today, and scrolls to it", async () => {
        const scrolled = vi.spyOn(HTMLElement.prototype, "scrollIntoView").mockImplementation(() => {});
        const wrapper = mountTimetable({ now });
        await nextTick();

        // the 09:40 tram is two minutes late, so it hasn't left at 09:40
        const nearest = wrapper.find("[data-nearest]");
        expect(nearest.attributes("data-nearest")).toBe("l6-2026-11-13-09:40");
        expect(scrolled.mock.contexts).toEqual([nearest.element]);
    });

    it("marks nothing on another day", () => {
        const wrapper = mountTimetable();

        expect(wrapper.find("[data-nearest]").exists()).toBe(false);
    });
});
