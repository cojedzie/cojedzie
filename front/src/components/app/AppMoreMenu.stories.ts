import AppMoreMenu from "./AppMoreMenu.vue";
import { Meta, StoryObj } from "@storybook/vue3-vite";

export default {
    title: "App/MoreMenu",
    component: AppMoreMenu,

    args: {
        entries: [],
        provider: "trojmiasto",
    },
} satisfies Meta<typeof AppMoreMenu>;

export const Default: StoryObj<typeof AppMoreMenu> = {};

export const WithEntries: StoryObj<typeof AppMoreMenu> = {
    args: {
        entries: [{ id: "departures", label: "Odjazdy", icon: "timetable", route: "departures", bottomBar: false }],
    },
};
