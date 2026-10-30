// @vitest-environment happy-dom
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { mount } from "@vue/test-utils";
import moment from "moment";
import TripSchedule from "@/components/trip/TripSchedule.vue";

const stops = ["a", "b", "c"].map((id, i) => ({
    id,
    name: `Stop ${id}`,
    order: i,
}));
const now = moment("2026-01-01T12:00:00");
const schedule = [
    { order: 0, stop: stops[0], departure: moment(now).subtract(20, "minutes") },
    { order: 1, stop: stops[1], departure: moment(now).add(5, "minutes") },
    { order: 2, stop: stops[2], departure: moment(now).add(10, "minutes") },
];

const mountSchedule = (attachTo?: HTMLElement) =>
    mount(TripSchedule, {
        props: { schedule, current: stops[1] },
        attachTo,
        global: {
            stubs: { StopLabel: { props: ["stop"], template: "<span>{{ stop.name }}</span>" } },
            directives: { dragscroll: {} },
            config: { globalProperties: { $isTouch: false } },
        },
    });

describe("TripSchedule", () => {
    beforeEach(() => {
        vi.useFakeTimers({ toFake: ["Date"] });
        vi.setSystemTime(now.toDate());
    });
    afterEach(() => vi.useRealTimers());

    it("lists the stops with their departure times", () => {
        const items = mountSchedule().findAll(".trip__stop");

        expect(items.map(i => i.find(".trip__departure").text())).toEqual(["11:40", "12:05", "12:10"]);
        expect(items[1].text()).toContain("Stop b");
    });

    it("marks the visited and the current stops", () => {
        const items = mountSchedule().findAll(".trip__stop");

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

        const wrapper = mountSchedule(host);

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

        const wrapper = mountSchedule(host);
        const list = wrapper.find(".trip__stops").element as HTMLElement;
        list.scrollLeft = 10;

        await wrapper.setProps({ current: stops[2] });

        expect(list.scrollLeft).toBe(10);
        spies.forEach(s => s.mockRestore());
        wrapper.unmount();
    });
});
