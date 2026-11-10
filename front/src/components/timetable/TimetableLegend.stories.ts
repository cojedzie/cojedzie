import { Meta, StoryObj } from "@storybook/vue3-vite";
import TimetableLegend from "./TimetableLegend.vue";
import type { Legend } from "@/timetable";

type Args = { legend: Legend };

export default {
    title: "Timetable/Parts/Legend",
    component: TimetableLegend,
} satisfies Meta<Args>;

type Story = StoryObj<Args>;

/** Under a line's list or table, when the line has several headsigns at the stop */
export const TwoHeadsigns: Story = {
    args: {
        legend: [
            { note: "a", headsign: "Łostowice" },
            { note: "b", headsign: "Siedlce" },
        ],
    },
};

export const ManyHeadsigns: Story = {
    args: {
        legend: [
            { note: "a", headsign: "Łostowice" },
            { note: "b", headsign: "Siedlce" },
            { note: "c", headsign: "Zajezdnia Wrzeszcz" },
            { note: "d", headsign: "Brama Oliwska" },
        ],
    },
};
