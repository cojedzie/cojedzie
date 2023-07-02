import UiSwitch from "./UiSwitch.vue";
import { ref } from "vue";

const Template = args => ({
    components: { UiSwitch },
    setup: () => {
        const value = ref(args.value);
        return { value, args };
    },
    template: "<ui-switch v-model:value='value' @update:value='args.onUpdate'/>",
});

export default {
    title: "UI/Switch",
    component: UiSwitch,

    argTypes: {
        value: {
            control: {
                type: "boolean",
            },
        },

        onUpdate: {
            action: "update:value",
        },
    },

    args: {
        value: true,
    },
};

export const On = {
    render: Template.bind({}),
    name: "On",

    args: {
        value: true,
    },
};

export const Off = {
    render: Template.bind({}),
    name: "Off",

    args: {
        value: false,
    },
};
