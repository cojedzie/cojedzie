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
            <div style="display: flex; justify-content: flex-end">
                <ui-section-settings v-bind="args">
                    <p style="margin: 0; padding: 0.5rem">Ustawienia sekcji</p>
                </ui-section-settings>
            </div>
        `,
    }),
};
