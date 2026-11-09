// @vitest-environment happy-dom
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { createPinia, setActivePinia } from "pinia";
import { flushPromises, mount } from "@vue/test-utils";
import { ref } from "vue";
import { Temporal } from "@/time";
import { DisplayTimeZoneKey } from "@/composables/useDisplayTimeZone";
import DeparturesDeparture from "@/components/departures/DeparturesDeparture.vue";
import { useDeparturesSettingsStore } from "@/stores/departures-settings";

const get = vi.hoisted(() => vi.fn());
vi.mock("@/api", () => ({ default: { get } }));

// 12:00 in Warsaw, the display time zone; the device's zone (UTC in tests) must not show through
const now = Temporal.Instant.from("2026-10-30T11:00:00Z");

const departure = (overrides = {}) =>
    ({
        key: "1",
        display: "Oliwa",
        line: { id: "6", symbol: "6", type: "tram" },
        stop: { id: "1", name: "Brama" },
        delay: 0,
        scheduled: now.add({ minutes: 10 }),
        estimated: now.add({ minutes: 10 }),
        trip: { id: "trip-1" },
        ...overrides,
    }) as any;

function mountDeparture(settings = {}, overrides = {}) {
    const pinia = createPinia();
    setActivePinia(pinia);
    useDeparturesSettingsStore().update(settings);

    return mount(DeparturesDeparture, {
        props: { departure: departure(overrides) },
        global: {
            plugins: [pinia],
            provide: { [DisplayTimeZoneKey as symbol]: ref("Europe/Warsaw") },
            stubs: {
                LineSymbol: true,
                StopLabel: true,
                UiIcon: true,
                UiTooltip: true,
                UiFold: { props: ["visible"], template: '<div v-if="visible" class="fold"><slot /></div>' },
                TripTimetable: {
                    props: ["timetable"],
                    template:
                        '<div class="trip">{{ timetable.map(stop => stop.departure.toString()).join(" ") }}</div>',
                },
            },
        },
    });
}

const time = (wrapper: ReturnType<typeof mountDeparture>) =>
    wrapper.find(".departure > .departure__time").text().replace(/\s+/g, " ");

describe("DeparturesDeparture", () => {
    beforeEach(() => {
        vi.useFakeTimers({ toFake: ["Date"] });
        vi.setSystemTime(now.epochMilliseconds);
        get.mockReset();
    });

    afterEach(() => vi.useRealTimers());

    it("shows the absolute time when relative times are off", () => {
        const wrapper = mountDeparture({ relativeTimes: false });

        expect(time(wrapper)).toBe("12:10");
    });

    it("shows the scheduled time and the delay when the departure is late", () => {
        const wrapper = mountDeparture({ relativeTimes: false }, { delay: 120, estimated: now.add({ minutes: 12 }) });

        expect(time(wrapper)).toBe("12:10 +120s 12:12");
    });

    it("shows the relative time when relative times are on", () => {
        const wrapper = mountDeparture({ relativeTimes: true, relativeTimesLimitEnabled: false });

        expect(time(wrapper)).toBe("za 10 minut");
    });

    it("shows the absolute time beyond the relative times limit", () => {
        const wrapper = mountDeparture({ relativeTimes: true, relativeTimesLimitEnabled: true, relativeTimesLimit: 5 });

        expect(time(wrapper)).toBe("12:10");
    });

    it("shows the absolute time of scheduled departures when relative times skip them", () => {
        const wrapper = mountDeparture(
            { relativeTimes: true, relativeTimesLimitEnabled: false, relativeTimesForScheduled: false },
            { estimated: null },
        );

        expect(time(wrapper)).toBe("12:10");
    });

    it("loads the trip when it is expanded", async () => {
        get.mockResolvedValue({
            status: 200,
            data: {
                id: "trip-1",
                timetable: [
                    { arrival: "2026-10-30T12:10:00+01:00", departure: "2026-10-30T12:10:00+01:00" },
                    { arrival: "2026-10-30T12:12:00+01:00", departure: "2026-10-30T12:12:00+01:00" },
                ],
            },
        });
        const wrapper = mountDeparture();

        expect(wrapper.find(".fold").exists()).toBe(false);
        expect(get).not.toHaveBeenCalled();

        await wrapper.find(".stop__actions button").trigger("click");
        await flushPromises();

        expect(get).toHaveBeenCalledWith("v1_trip_details", { params: { id: "trip-1" }, version: "^1.1" });
        expect(wrapper.find(".trip").text()).toBe("2026-10-30T11:10:00Z 2026-10-30T11:12:00Z");
    });

    it("shifts the trip's times by the delay exactly, also across a DST change", async () => {
        // Warsaw leaves summer time at 01:00 UTC on 2026-10-25: 02:59 CEST plus 2 minutes is 02:01 CET
        get.mockResolvedValue({
            status: 200,
            data: {
                id: "trip-1",
                timetable: [{ arrival: "2026-10-25T02:59:00+02:00", departure: "2026-10-25T02:59:00+02:00" }],
            },
        });
        const wrapper = mountDeparture({}, { delay: 120 });

        await wrapper.find(".stop__actions button").trigger("click");
        await flushPromises();

        expect(wrapper.find(".trip").text()).toBe("2026-10-25T01:01:00Z");
    });
});
