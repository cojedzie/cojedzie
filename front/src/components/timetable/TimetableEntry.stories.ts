import { Meta, StoryObj } from "@storybook/vue3-vite";
import TimetableEntry from "./TimetableEntry.vue";
import LineSymbol from "@/components/line/LineSymbol.vue";
import { buildLineLists, type LineListEntry } from "@/timetable";
import { formatTime } from "@/time";
import { date, lines, liveDay, plannedDay, timeZone } from "./timetable.fixtures";
import type { Departure, Line } from "@/model";

// one run of the fixture, as the list per line hands it to its cell
function entry(departures: Departure[], line: Line, at: string): LineListEntry {
    return buildLineLists(departures, date, timeZone)
        .find(list => list.line.id === line.id)
        .entries.find(candidate => formatTime(candidate.planned) === at);
}

type Args = { entry: LineListEntry; withLine?: boolean };

export default {
    title: "Timetable/Parts/Entry",
    argTypes: { entry: { control: false } },
} satisfies Meta<Args>;

type Story = StoryObj<Args>;

// in a cell as wide as the list's: the entry is the cell's content
const render: Story["render"] = args => ({
    components: { TimetableEntry, LineSymbol },
    setup: () => ({ args }),
    template: `
        <div style="inline-size: 5rem; padding: 0.25rem 0.5rem; border-block-end: 1px solid var(--color-divider)">
            <timetable-entry :entry="args.entry" :note="args.entry.note">
                <template v-if="args.withLine" #line>
                    <line-symbol :line="args.entry.departure.line" simple style="font-size: var(--text-xs)" />
                </template>
            </timetable-entry>
        </div>
    `,
});

/** A planned run: its time only */
export const Planned: Story = { render, args: { entry: entry(plannedDay, lines.eight, "10:10") } };

/** A run of a line with several headsigns: its time with the headsign's letter */
export const WithNote: Story = { render, args: { entry: entry(plannedDay, lines.six, "10:25") } };

/** A live run late by two minutes: the planned time struck through, the estimate and the delay */
export const Late: Story = { render, args: { entry: entry(liveDay, lines.six, "09:40") } };

/** A live run early: the delay in red */
export const Early: Story = { render, args: { entry: entry(liveDay, lines.six, "09:55") } };

/** With the line's symbol in the `line` slot, as the combined list shows it */
export const WithLine: Story = { render, args: { entry: entry(liveDay, lines.bus, "10:02"), withLine: true } };
