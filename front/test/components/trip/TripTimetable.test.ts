// @vitest-environment happy-dom
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { mount } from "@vue/test-utils";
import { ref } from "vue";
import { Temporal } from "@/time";
import { DisplayTimeZoneKey } from "@/composables/useDisplayTimeZone";
import TripTimetable from "@/components/trip/TripTimetable.vue";

const stops = ["a", "b", "c"].map((id, i) => ({
    id,
    name: `Stop ${id}`,
    order: i,
}));
// 12:00 in Warsaw, the display time zone
const now = Temporal.Instant.from("2026-01-01T11:00:00Z");
const timetable = [
    { order: 0, stop: stops[0], departure: now.subtract({ minutes: 20 }) },
    { order: 1, stop: stops[1], departure: now.add({ minutes: 5 }) },
    { order: 2, stop: stops[2], departure: now.add({ minutes: 10 }) },
];

const mountTimetable = (attachTo?: HTMLElement) =>
    mount(TripTimetable, {
        props: { timetable, current: stops[1] },
        attachTo,
        global: {
            provide: { [DisplayTimeZoneKey as symbol]: ref("Europe/Warsaw") },
            stubs: { StopLabel: { props: ["stop"], template: "<span>{{ stop.name }}</span>" } },
            directives: { dragscroll: {} },
            config: { globalProperties: { $isTouch: false } },
        },
    });

describe("TripTimetable", () => {
    beforeEach(() => {
        vi.useFakeTimers({ toFake: ["Date"] });
        vi.setSystemTime(now.epochMilliseconds);
    });
    afterEach(() => vi.useRealTimers());

    it("lists the stops with their departure times", () => {
        const items = mountTimetable().findAll(".trip__stop");

        expect(items.map(i => i.find(".trip__departure").text())).toEqual(["11:40", "12:05", "12:10"]);
        expect(items[1].text()).toContain("Stop b");
    });

    it("marks the visited and the current stops", () => {
        const items = mountTimetable().findAll(".trip__stop");

        expect(items.map(i => i.classes("trip__stop--visited"))).toEqual([true, false, false]);
        expect(items.map(i => i.classes("trip__stop--current"))).toEqual([false, true, false]);
    });

    it("scrolls the list to the current stop after mounting", () => {
        const host = document.createElement("div");
        document.body.appendChild(host);
        const proto = HTMLElement.prototype;
        const spies = [
            vi.spyOn(proto, "offsetLeft", "get").mockReturnValue(400),
            vi.spyOn(proto, "clientWidth", "get").mockReturnValue(100),
        ];

        const wrapper = mountTimetable(host);

        expect((wrapper.find(".trip__stops").element as HTMLElement).scrollLeft).toBe(300);
        spies.forEach(s => s.mockRestore());
        wrapper.unmount();
    });

    it("does not scroll again when it re-renders", async () => {
        const host = document.createElement("div");
        document.body.appendChild(host);
        const spies = [
            vi.spyOn(HTMLElement.prototype, "offsetLeft", "get").mockReturnValue(400),
            vi.spyOn(HTMLElement.prototype, "clientWidth", "get").mockReturnValue(100),
        ];

        const wrapper = mountTimetable(host);
        const list = wrapper.find(".trip__stops").element as HTMLElement;
        list.scrollLeft = 10;

        await wrapper.setProps({ current: stops[2] });

        expect(list.scrollLeft).toBe(10);
        spies.forEach(s => s.mockRestore());
        wrapper.unmount();
    });
});
