import UiMap from "./UiMap.vue";
import { StoryObj } from "@storybook/vue3";

const Template = args => ({
    components: { UiMap },
    setup: () => {
        return { args };
    },
    template: "<ui-map v-bind='args' />",
});

export default {
    title: "Ui/Map",
    component: UiMap,

    argTypes: {
        zoom: {
            control: {
                type: "range",
                min: 1,
                max: 17,
            },
        },
    },

    args: {
        zoom: 12,

        center: {
            lat: 54.348056,
            lng: 18.655,
        },
    },

    decorators: [
        () => ({
            template: '<div style="width: 800px; height: 600px"><story /></div>',
        }),
    ],
};

export const Default: StoryObj<typeof UiMap> = {
    render: Template.bind({}),
};
