import { Meta, StoryObj } from "@storybook/vue3-vite";
import TimetableListPerLine from "./TimetableListPerLine.vue";
import { buildLineLists } from "@/timetable";
import type { Departure } from "@/model";
import { between, date, lines, liveDay, ofLine, plannedDay, timeZone } from "./timetable.fixtures";

// The view gets the day's departures through its builder; the stories show a few of them, enough for each state.

type Args = { departures: Departure[]; width?: string };

export default {
    title: "Timetable/ListPerLine",
    argTypes: { departures: { control: false } },
} satisfies Meta<Args>;

type Story = StoryObj<Args>;

const render: Story["render"] = args => ({
    components: { TimetableListPerLine },
    setup: () => ({ args, lists: buildLineLists(args.departures, date, timeZone) }),
    template: `
        <div :style="{ inlineSize: args.width ?? '390px' }">
            <timetable-list-per-line :lists="lists" />
        </div>
    `,
});

/** An hour and a half of planned runs, one list per line; line 6 has two headsigns */
export const Planned: Story = { render, args: { departures: between(plannedDay, "09:00", "10:30") } };

/** The same runs at 09:40: the earlier ones departed, the ones around now with their estimates */
export const Live: Story = { render, args: { departures: between(liveDay, "09:00", "10:30") } };

/** The night line: runs after midnight at both ends of the day */
export const NightLine: Story = { render, args: { departures: ofLine(plannedDay, lines.night) } };

/** A morning on a desktop: the cells fill the width */
export const Desktop: Story = { render, args: { departures: between(liveDay, "06:00", "11:00"), width: "960px" } };

export const Empty: Story = { render, args: { departures: [] } };
