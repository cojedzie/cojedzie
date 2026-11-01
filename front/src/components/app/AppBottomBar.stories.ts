import AppBottomBar from "./AppBottomBar.vue";
import { Meta, StoryObj } from "@storybook/vue3-vite";

export default {
    title: "App/BottomBar",
    component: AppBottomBar,

    args: {
        provider: "trojmiasto",
    },

    // the bar is only shown below 768px, so the story opens in a phone viewport
    globals: { viewport: { value: "mobile2", isRotated: false } },
} satisfies Meta<typeof AppBottomBar>;

export const Default: StoryObj<typeof AppBottomBar> = {
    render: args => ({
        components: { AppBottomBar },
        setup: () => ({ args }),
        // the bar is fixed to the viewport, the transform makes this frame its containing block
        template: `
            <div style="width: 375px; height: 200px; transform: translateZ(0); border: 1px solid #ccc">
                <app-bottom-bar v-bind="args" />
            </div>
        `,
    }),
};
