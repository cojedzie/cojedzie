import { Meta, StoryObj } from "@storybook/vue3-vite";
import { userEvent, within } from "storybook/test";
import TimetableLinePicker from "./TimetableLinePicker.vue";
import type { Line } from "@/model";
import { lines } from "./timetable.fixtures";

const stopLines = [
    { line: lines.six, directions: ["Łostowice"] },
    { line: lines.eight, directions: ["Jelitkowo"] },
    { line: lines.bus, directions: ["Przymorze Wielkie"] },
    { line: lines.night, directions: ["Dworzec Główny", "Oliwa"] },
];

export default {
    title: "Timetable/Line Picker",
    component: TimetableLinePicker,
    args: {
        line: lines.eight,
        lines: stopLines,
        lineTo: (line: Line) => ({
            name: "timetable-line-stop",
            params: { provider: "trojmiasto", line: line.id, stop: "s1" },
        }),
        allTo: { name: "timetable-stop", params: { provider: "trojmiasto", stop: "s1" } },
    },
    argTypes: { lines: { control: false }, lineTo: { control: false }, allTo: { control: false } },
    render: args => ({
        components: { TimetableLinePicker },
        setup: () => ({ args }),
        // in a heading, as the timetable's layout shows it
        template: `
            <h1 style="display: flex; align-items: center; gap: 0.75rem; margin: 0; font-size: 1.875rem; font-weight: 500">
                Rozkład linii <timetable-line-picker v-bind="args" />
            </h1>
        `,
    }),
    play: async ({ canvasElement }) => {
        await userEvent.click(within(canvasElement).getByRole("button", { name: /zmień linię/ }));
    },
} satisfies Meta<typeof TimetableLinePicker>;

type Story = StoryObj<typeof TimetableLinePicker>;

/** The stop's lines with where they go from it, the current one marked, and all of them together */
export const Open: Story = {};

/** Across the page on a phone, each row a 44px tap target */
export const Phone: Story = { globals: { viewport: { value: "mobile2", isRotated: false } } };
