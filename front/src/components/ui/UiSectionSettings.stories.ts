import UiSectionSettings from "./UiSectionSettings.vue";
import { Meta, StoryObj } from "@storybook/vue3-vite";

export default {
    title: "Ui/Section/Settings",
    component: UiSectionSettings,

    argTypes: {
        label: { control: { type: "text" } },
        placement: {
            options: ["left-start", "bottom-end", "right-start", "auto"],
            control: { type: "select" },
        },
    },

    args: {
        label: "ustawienia",
        placement: "bottom-end",
    },
} satisfies Meta<typeof UiSectionSettings>;

export const Default: StoryObj<typeof UiSectionSettings> = {
    render: args => ({
        components: { UiSectionSettings },
        setup: () => ({ args }),
        template: `
            <div class="flex justify-end">
                <ui-section-settings v-bind="args">
                    <p class="mb-0 p-2">Ustawienia sekcji</p>
                </ui-section-settings>
            </div>
        `,
    }),
};
