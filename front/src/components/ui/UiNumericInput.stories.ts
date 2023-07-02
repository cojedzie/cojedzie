import UiNumericInput from "./UiNumericInput.vue";
import { ref } from "vue";
import { StoryObj } from "@storybook/vue3";

const Template = args => ({
    components: { UiNumericInput },
    setup: () => {
        const value = ref(args.value);
        return { args, value };
    },
    template: `<ui-numeric-input v-bind='args' v-model:value='value' @update:value='args.onUpdate'/>`,
});

export default {
    title: "UI/Numeric Input",
    component: UiNumericInput,

    argTypes: {
        value: {
            defaultValue: 0,

            control: {
                type: "number",
            },
        },

        min: {
            control: {
                type: "number",
            },
        },

        max: {
            control: {
                type: "number",
            },
        },

        step: {
            defaultValue: 1,

            control: {
                type: "number",
            },
        },

        onUpdate: {
            action: "update:value",
        },
    },
};

export const Default: StoryObj<typeof UiNumericInput> = {
    render: Template.bind({}),
};
