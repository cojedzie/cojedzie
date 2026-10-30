// @vitest-environment happy-dom
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { createPinia, setActivePinia } from "pinia";
import { flushPromises, mount } from "@vue/test-utils";
import moment from "moment";
import DeparturesDeparture from "@/components/departures/DeparturesDeparture.vue";
import { useDeparturesSettingsStore } from "@/stores/departures-settings";

const get = vi.hoisted(() => vi.fn());
vi.mock("@/api", () => ({ default: { get } }));

const now = moment("2026-10-30T12:00:00");

const departure = (overrides = {}) =>
    ({
        key: "1",
        display: "Oliwa",
        line: { id: "6", symbol: "6", type: "tram" },
        stop: { id: "1", name: "Brama" },
        delay: 0,
        scheduled: now.clone().add(10, "minutes"),
        estimated: now.clone().add(10, "minutes"),
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
            stubs: {
                LineSymbol: true,
                StopLabel: true,
                UiIcon: true,
                UiTooltip: true,
                UiFold: { props: ["visible"], template: '<div v-if="visible" class="fold"><slot /></div>' },
                TripSchedule: { props: ["schedule"], template: '<div class="trip">{{ schedule.length }}</div>' },
            },
        },
    });
}

const time = (wrapper: ReturnType<typeof mountDeparture>) =>
    wrapper.find(".departure > .departure__time").text().replace(/\s+/g, " ");

describe("DeparturesDeparture", () => {
    beforeEach(() => {
        vi.useFakeTimers({ toFake: ["Date"] });
        vi.setSystemTime(now.toDate());
        get.mockReset();
    });

    afterEach(() => vi.useRealTimers());

    it("shows the absolute time when relative times are off", () => {
        const wrapper = mountDeparture({ relativeTimes: false });

        expect(time(wrapper)).toBe("12:10");
    });

    it("shows the scheduled time and the delay when the departure is late", () => {
        const wrapper = mountDeparture(
            { relativeTimes: false },
            { delay: 120, estimated: now.clone().add(12, "minutes") },
        );

        expect(time(wrapper)).toBe("12:10 +120s 12:12");
    });

    it("shows the relative time when relative times are on", () => {
        const wrapper = mountDeparture({ relativeTimes: true, relativeTimesLimitEnabled: false });

        expect(time(wrapper)).toBe(moment.duration(10, "minutes").humanize(true));
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
                schedule: [
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

        expect(get).toHaveBeenCalledWith("v1_trip_details", { params: { id: "trip-1" }, version: "^1.0" });
        expect(wrapper.find(".trip").text()).toBe("2");
    });
});
