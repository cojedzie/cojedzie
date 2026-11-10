import { Meta, StoryObj } from "@storybook/vue3-vite";
import TimetableLineHeading from "./TimetableLineHeading.vue";
import type { Line } from "@/model";
import { lines } from "./timetable.fixtures";

// the headsign goes in the default slot
type Args = { line: Line; headsign: string; width?: string };

export default {
    title: "Timetable/Parts/LineHeading",
    component: TimetableLineHeading,
    argTypes: { line: { control: false } },
    args: { line: lines.six, headsign: "Łostowice" },
} satisfies Meta<Args>;

type Story = StoryObj<Args>;

const render: Story["render"] = args => ({
    components: { TimetableLineHeading },
    setup: () => ({ args }),
    template: `
        <div :style="{ inlineSize: args.width ?? '390px' }">
            <timetable-line-heading :line="args.line">{{ args.headsign }}</timetable-line-heading>
        </div>
    `,
});

/** Underlined in the colour of the line's type */
export const Tram: Story = { render };

export const Bus: Story = { render, args: { line: lines.bus, headsign: "Oliwa PKP" } };

export const NightLine: Story = { render, args: { line: lines.night, headsign: "Dworzec Główny" } };

/** A headsign longer than the room is cut */
export const LongHeadsign: Story = {
    render,
    args: { headsign: "Zajezdnia Wrzeszcz przez Bramę Oliwską i Dworzec Główny", width: "240px" },
};
