import UiPin from "./UiPin.vue";
import { StoryObj } from "@storybook/vue3";

const Template = args => ({
    components: { UiPin },
    setup: () => {
        return { args };
    },
    template: "<ui-pin v-bind='args'><ui-icon icon='stop' /></ui-pin>",
});

export default {
    title: "UI/Map/Pin",
    component: UiPin,

    argTypes: {
        variant: {
            options: ["outline", "filled", "filled-outline"],
            control: "select",
        },
    },
};

export const Outline: StoryObj<typeof UiPin> = {
    render: Template.bind({}),
    args: {
        variant: "outline",
    },
};

export const Filled: StoryObj<typeof UiPin> = {
    render: Template.bind({}),
    args: {
        variant: "filled",
    },
};

export const FilledOutline: StoryObj<typeof UiPin> = {
    render: Template.bind({}),
    args: {
        variant: "filled-outline",
    },
};
