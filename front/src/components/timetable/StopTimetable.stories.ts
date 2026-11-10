import { Meta, StoryObj } from "@storybook/vue3-vite";
import { ref, shallowRef } from "vue";
import StopTimetable, { type StopTimetableGrouping, type StopTimetableView } from "./StopTimetable.vue";
import type { Departure } from "@/model";
import { Temporal } from "@/time";
import { between, date, lines, liveDay, ofLine, timeZone } from "./timetable.fixtures";

type Args = {
    departures: Departure[];
    view?: StopTimetableView;
    grouping?: StopTimetableGrouping;
    lineFixed?: boolean;
};

export default {
    title: "Timetable/StopTimetable",
    // the controls follow the screen: smaller on a phone
    globals: { viewport: { value: "mobile2", isRotated: false } },
    args: { departures: between(liveDay, "06:00", "11:00") },
    argTypes: {
        departures: { control: false },
        view: { control: { type: "inline-radio" }, options: ["list", "hours"] },
        grouping: { control: { type: "inline-radio" }, options: ["line", "combined"] },
    },
} satisfies Meta<Args>;

type Story = StoryObj<Args>;

// the fixture's day, within a week the timetable knows
const range = { from: date.subtract({ days: 2 }), to: date.add({ days: 5 }) };

const render: Story["render"] = args => ({
    components: { StopTimetable },
    setup: () => ({
        args,
        timeZone,
        range,
        // the day is the fixture's, whichever is picked: the stories have one day of data
        day: shallowRef<Temporal.PlainDate>(date),
        view: ref(args.view ?? "list"),
        grouping: ref(args.grouping ?? "line"),
    }),
    template: `
        <stop-timetable
            v-model:date="day"
            v-model:view="view"
            v-model:grouping="grouping"
            :departures="args.departures"
            :time-zone="timeZone"
            :range="range"
            :line-fixed="args.lineFixed"
        />
    `,
});

/** A morning at the stop, seen at 09:40: switch between the list and hours, per line and combined */
export const Stop: Story = { render };

export const Hours: Story = { render, args: { view: "hours" } };

/** One line at the stop: no grouping to choose */
export const FixedLine: Story = {
    render,
    args: {
        departures: ofLine(between(liveDay, "06:00", "11:00"), lines.six),
        lineFixed: true,
        view: "hours",
    },
};

export const Empty: Story = { render, args: { departures: [] } };

export const Desktop: Story = { render, globals: { viewport: { value: "desktop", isRotated: false } } };

/** A whole day, as the page will hand it over: about 150 runs of four lines */
export const WholeDay: Story = { render, args: { departures: liveDay } };
