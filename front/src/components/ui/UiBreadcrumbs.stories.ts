/*
 * Copyright (C) 2026 Kacper Donat
 *
 * @author Kacper Donat <kacper@kadet.net>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

import UiBreadcrumbs from "./UiBreadcrumbs.vue";
import type { Meta, StoryObj } from "@storybook/vue3-vite";
import type { Crumb } from "@/breadcrumbs";
import type { Line, Stop } from "@/model";
import StopLabel from "@/components/stop/StopLabel.vue";
import LineCrumb from "@/components/timetable/LineCrumb.vue";

const params = { provider: "trojmiasto", line: "l8", stop: "s1" };

const tram = (symbol: string): Line => ({
    id: "l8",
    symbol,
    type: "tram",
    night: false,
    fast: false,
    $type: "vnd.cojedzie.line",
});
const stopNamed = (name: string): Stop => ({ id: "s1", name, variant: "01", $type: "vnd.cojedzie.stop" });

const rozklad: Crumb = {
    label: "Rozkład",
    icon: "calendar",
    to: { name: "timetable", params, query: {} },
    current: false,
    pending: false,
};
const lineCrumb = (line: Line, current = false): Crumb => ({
    label: `Linia ${line.symbol}`,
    content: { component: LineCrumb, props: { line } },
    to: { name: "timetable-line", params, query: {} },
    current,
    pending: false,
});
const stopCrumb = (stop: Stop, name: string): Crumb => ({
    label: `${stop.name} ${stop.variant}`,
    content: { component: StopLabel, props: { stop, truncate: true } },
    to: { name, params, query: {} },
    current: true,
    pending: false,
});

/** The trails of the timetable's pages, as `buildTrail` makes them from the timetable's crumbs */
const trails = {
    section: [{ ...rozklad, current: true }],
    stop: [rozklad, stopCrumb(stopNamed("Brama Wyżynna"), "timetable-stop")],
    line: [rozklad, lineCrumb(tram("8"), true)],
    lineAtStop: [rozklad, lineCrumb(tram("8")), stopCrumb(stopNamed("Brama Wyżynna"), "timetable-line-stop")],
    // while the pages load: what each crumb is
    loading: [
        rozklad,
        { ...lineCrumb(tram("8")), label: "Linia", content: undefined, pending: true },
        { ...stopCrumb(stopNamed(""), "timetable-line-stop"), label: "Przystanek", content: undefined, pending: true },
    ],
    longStop: [
        rozklad,
        lineCrumb(tram("N13")),
        stopCrumb(stopNamed("Gdańsk Wrzeszcz PKM (Dworzec Kolejowy)"), "timetable-line-stop"),
    ],
};

export default {
    title: "Ui/Breadcrumbs",
    component: UiBreadcrumbs,
    args: { crumbs: trails.lineAtStop },
} satisfies Meta<typeof UiBreadcrumbs>;

type Story = StoryObj<typeof UiBreadcrumbs>;

/** Every trail of the timetable at the given width, so phone and desktop read side by side */
const atWidth = (width: number): Story => ({
    render: () => ({
        components: { UiBreadcrumbs },
        setup: () => ({ trails, width: `${width}px` }),
        template: `
            <div :style="{ width, display: 'grid', gap: '16px' }">
                <ui-breadcrumbs v-for="(crumbs, name) in trails" :key="name" :crumbs="crumbs" />
            </div>
        `,
    }),
});

export const Default: Story = {};

export const Phone = atWidth(360);

export const Desktop = atWidth(960);

/**
 * A long stop name as the trail narrows (a small phone, larger text): the stop's name shortens first; once it would
 * drop under 6rem "Rozkład" becomes its icon, and last the line's crumb becomes "…" (still a link for screen readers)
 */
export const Collapsing: Story = {
    args: { crumbs: trails.longStop },
    render: args => ({
        components: { UiBreadcrumbs },
        setup: () => ({ args, widths: ["360px", "300px", "250px", "200px"] }),
        template: `
            <div style="display: grid; gap: 16px">
                <div v-for="width in widths" :key="width" :style="{ width, outline: '1px dashed #ccc' }">
                    <ui-breadcrumbs v-bind="args" />
                </div>
            </div>
        `,
    }),
};
