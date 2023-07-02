import UiTooltip from "./UiTooltip.vue";
import { centered } from "@/stories/decorators";

const Template = args => ({
    components: { UiTooltip },
    setup: () => {
        return { args };
    },
    template: `
        <button class='btn btn-primary'>
            <ui-tooltip v-bind='args'>${args.content}</ui-tooltip>
            I've got a tooltip!
        </button>
    `,
});

export default {
    title: "UI/Tooltip",
    component: UiTooltip,

    args: {
        content: "I'm a tooltip",
    },

    argTypes: {
        content: {
            control: {
                type: "text",
            },
        },

        placement: {
            options: ["top", "left", "right", "bottom"].flatMap(placement => [
                `${placement}-start`,
                placement,
                `${placement}-end`,
            ]),
            control: "select",
        },

        triggers: {
            defaultValue: ["hover", "focus", "long-press"],
            options: ["hover", "focus", "long-press"],
            control: "check",
        },

        delay: {
            defaultValue: 400,

            control: {
                type: "number",
            },
        },
    },

    decorators: [centered],
};

export const Default = {
    render: Template.bind({}),
};

export const WithHtmlContent = {
    render: Template.bind({}),
    name: "With HTML Content",
    args: {
        content: "<i>Rich</i> Content <ui-icon icon='line-trolleybus' />",
    },
};
