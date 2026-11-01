import UiSheet from "./UiSheet.vue";
import { Meta, StoryObj } from "@storybook/vue3-vite";
import { ref } from "vue";

export default {
    title: "Ui/Sheet",
    component: UiSheet,

    argTypes: {
        title: { control: { type: "text" } },
    },

    args: {
        title: "Wybierz lokalizację",
    },
} satisfies Meta<typeof UiSheet>;

export const Default: StoryObj<typeof UiSheet> = {
    render: args => ({
        components: { UiSheet },
        setup: () => ({ args, open: ref(true) }),
        template: `
            <button class="btn btn-primary" @click="open = true">otwórz</button>
            <ui-sheet v-if="open" v-bind="args" @leave="open = false">
                <p class="mb-0">Treść arkusza</p>
            </ui-sheet>
        `,
    }),
};
