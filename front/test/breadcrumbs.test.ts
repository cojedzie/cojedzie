// @vitest-environment happy-dom
import { describe, expect, it } from "vitest";
import { createMemoryHistory, createRouter } from "vue-router";
import { buildTrail, type TrailData } from "@/breadcrumbs";
import type { Line, Stop } from "@/model";
import { routes } from "@/routes";

const router = createRouter({ routes, history: createMemoryHistory() });

const trail = (url: string, data: TrailData = {}) => buildTrail(router.resolve(url), router, data);
const labels = (url: string, data: TrailData = {}) => trail(url, data).map(({ label }) => label);

const line: Line = { id: "l8", symbol: "8", type: "tram", night: false, fast: false, $type: "vnd.cojedzie.line" };
const stop: Stop = { id: "s1", name: "Brama Wyżynna", variant: "01", $type: "vnd.cojedzie.stop" };

describe("timetable breadcrumbs", () => {
    it("the section's start page is the lone crumb", () => {
        expect(labels("/trojmiasto/timetable")).toEqual(["Rozkład"]);
    });

    it("a stop's timetable is under the section", () => {
        expect(labels("/trojmiasto/timetable/stops/s1", { stop })).toEqual(["Rozkład", "Brama Wyżynna 01"]);
    });

    it("a line's page is under the section", () => {
        expect(labels("/trojmiasto/timetable/lines/l8", { line })).toEqual(["Rozkład", "Linia 8"]);
    });

    it("a line at a stop is under the line's page, although the router has them side by side", () => {
        expect(labels("/trojmiasto/timetable/lines/l8/stops/s1", { line, stop })).toEqual([
            "Rozkład",
            "Linia 8",
            "Brama Wyżynna 01",
        ]);
    });

    it("names a stop without a code by its name alone", () => {
        expect(labels("/trojmiasto/timetable/stops/s1", { stop: { ...stop, variant: undefined } })).toEqual([
            "Rozkład",
            "Brama Wyżynna",
        ]);
    });

    it("says what a crumb is until its line or stop is loaded", () => {
        expect(labels("/trojmiasto/timetable/lines/l8/stops/s1")).toEqual(["Rozkład", "Linia", "Przystanek"]);
    });

    it("never names a line or stop of another route", () => {
        expect(labels("/trojmiasto/timetable/lines/l10/stops/s2", { line, stop })).toEqual([
            "Rozkład",
            "Linia",
            "Przystanek",
        ]);
    });

    it("shows a loaded line and stop as themselves, never one of another route", () => {
        const loaded = trail("/trojmiasto/timetable/lines/l8/stops/s1", { line, stop });
        expect(loaded.map(({ content }) => content?.props)).toEqual([undefined, { line }, { stop, truncate: true }]);

        const other = trail("/trojmiasto/timetable/lines/l10/stops/s2", { line, stop });
        expect(other.map(({ content }) => content)).toEqual([undefined, undefined, undefined]);
    });

    it("marks a crumb pending until its line or stop is loaded", () => {
        const loading = trail("/trojmiasto/timetable/lines/l8/stops/s1");
        expect(loading.map(({ pending }) => pending)).toEqual([false, true, true]);

        const loaded = trail("/trojmiasto/timetable/lines/l8/stops/s1", { line, stop });
        expect(loaded.map(({ pending }) => pending)).toEqual([false, false, false]);
    });

    it("gives the section's crumb the section's icon, to show when space runs out", () => {
        expect(trail("/trojmiasto/timetable/stops/s1").map(({ icon }) => icon)).toEqual(["calendar", undefined]);
    });

    it("links every crumb but the current one, which is the last", () => {
        const crumbs = trail("/trojmiasto/timetable/lines/l8/stops/s1", { line, stop });

        expect(crumbs.map(({ current }) => current)).toEqual([false, false, true]);
        expect(crumbs[0].to).toEqual({ name: "timetable", params: { provider: "trojmiasto" }, query: {} });
        expect(crumbs[1].to).toEqual({
            name: "timetable-line",
            params: { provider: "trojmiasto", line: "l8" },
            query: {},
        });
    });

    it("keeps the track up to the line's page, but not the date", () => {
        const [root, onLine] = trail("/trojmiasto/timetable/lines/l8/stops/s1?track=t1&date=2026-11-07");

        expect(root.to).toMatchObject({ query: {} });
        expect(onLine.to).toMatchObject({ query: { track: "t1" } });
    });

    it("a page without a crumb has no trail", () => {
        expect(trail("/trojmiasto")).toEqual([]);
    });
});
