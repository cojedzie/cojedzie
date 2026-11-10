// @vitest-environment happy-dom
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { mount } from "@vue/test-utils";
import { ref } from "vue";
import { Temporal } from "@/time";
import { DisplayTimeZoneKey } from "@/composables/useDisplayTimeZone";
import MessagesMessage from "@/components/messages/MessagesMessage.vue";

function mountMessage(props = {}) {
    return mount(MessagesMessage, {
        props: { message: "Tram detour", ...props },
        global: {
            provide: { [DisplayTimeZoneKey as symbol]: ref("Europe/Warsaw") },
            stubs: {
                UiIcon: { props: ["icon"], template: '<i :class="icon" />' },
                StopLabel: { props: ["stop"], template: '<span class="stop">{{ stop.name }}</span>' },
                LineSymbol: { props: ["line"], template: '<span class="line">{{ line.symbol }}</span>' },
            },
        },
    });
}

describe("MessagesMessage", () => {
    it("renders the lines and the stops it concerns", () => {
        const wrapper = mountMessage({
            lines: [{ id: "6", symbol: "6" }],
            stops: [
                { id: "1", name: "Brama" },
                { id: "2", name: "Oliwa" },
            ],
        });

        expect(wrapper.findAll(".line").map(line => line.text())).toEqual(["6"]);
        expect(wrapper.findAll(".stop").map(stop => stop.text())).toEqual(["Brama", "Oliwa"]);
    });

    it("renders neither lines nor stops when there are none", () => {
        const wrapper = mountMessage();

        expect(wrapper.find(".lines").exists()).toBe(false);
        expect(wrapper.find(".stop-list").exists()).toBe(false);
    });

    it("renders the validity and the given tag", () => {
        const wrapper = mountMessage({
            tag: "li",
            validFrom: Temporal.Instant.from("2020-01-02T10:00:00Z"),
            validTo: Temporal.Instant.from("2020-01-03T10:00:00Z"),
        });

        expect(wrapper.element.tagName).toBe("LI");
        expect(wrapper.find(".validity").text().replace(/\s+/g, " ")).toBe(
            "ważne od 2 stycznia 2020 do 3 stycznia 2020",
        );
    });

    describe("validity today", () => {
        // 12:00 in Warsaw
        const now = Temporal.Instant.from("2026-11-10T11:00:00Z");

        beforeEach(() => {
            vi.useFakeTimers({ toFake: ["Date"] });
            vi.setSystemTime(now.epochMilliseconds);
        });
        afterEach(() => vi.useRealTimers());

        const validity = (props: object) => mountMessage(props).find(".validity").text().replace(/\s+/g, " ");

        it("says today with the time, and only the time for an end later the same day", () => {
            expect(validity({ validFrom: now.subtract({ hours: 2 }), validTo: now.add({ hours: 2 }) })).toBe(
                "ważne od dzisiaj o 10:00 do 14:00",
            );
        });

        it("gives other days as dates, as Polish declines day words after its prepositions", () => {
            expect(validity({ validTo: now.add({ hours: 24 }) })).toBe("ważne do 11 listopada 2026");
        });

        it("says today for the end that is today, and the date for the other", () => {
            expect(validity({ validFrom: now, validTo: now.add({ hours: 48 }) })).toBe(
                "ważne od dzisiaj o 12:00 do 12 listopada 2026",
            );
        });
    });
});
