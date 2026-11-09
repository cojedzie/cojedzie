import UiToggleGroup, { type UiToggleGroupOption } from "./UiToggleGroup.vue";
import { Meta, StoryObj } from "@storybook/vue3-vite";
import { type ConcreteComponent, ref } from "vue";

// a generic component doesn't fit Meta<typeof UiToggleGroup>, so the stories use it with string values
type Args = {
    label: string;
    modelValue: string;
    options: UiToggleGroupOption<string>[];
    variant?: "normal" | "primary";
    size?: "small" | "normal" | "large";
    "onUpdate:modelValue"?: (value: string) => void;
};
const component = UiToggleGroup as unknown as ConcreteComponent<Args>;

export default {
    title: "Ui/ToggleGroup",
    component,

    argTypes: {
        label: { control: { type: "text" } },
        variant: { control: { type: "inline-radio" }, options: ["normal", "primary"] },
        size: { control: { type: "inline-radio" }, options: ["small", "normal", "large"] },
        "onUpdate:modelValue": { action: "update:modelValue" },
    },

    args: {
        label: "Widok",
        modelValue: "list",
        options: [
            { value: "list", label: "Lista" },
            { value: "hours", label: "Godziny" },
        ],
    },
} satisfies Meta<Args>;

type Story = StoryObj<Args>;

const render =
    (template = `<ui-toggle-group v-bind="args" v-model="value" />`): Story["render"] =>
    args => ({
        components: { UiToggleGroup },
        setup: () => ({ args, value: ref(args.modelValue) }),
        template,
    });

export const TwoOptions: Story = {
    render: render(),
};

export const ThreeOptions: Story = {
    render: render(),
    args: {
        label: "Kierunek",
        modelValue: "both",
        options: [
            { value: "there", label: "Tam" },
            { value: "both", label: "Oba" },
            { value: "back", label: "Z powrotem" },
        ],
    },
};

export const WithIcons: Story = {
    render: render(),
    args: {
        label: "Widok",
        modelValue: "list",
        options: [
            { value: "list", label: "Lista", icon: "track" },
            { value: "hours", label: "Godziny", icon: "timetable" },
        ],
    },
};

export const Primary: Story = {
    render: render(),
    args: {
        ...WithIcons.args,
        variant: "primary",
    },
};

export const Sizes: Story = {
    render: render(`
        <div style="display: flex; flex-direction: column; align-items: flex-start; gap: 1rem">
            <ui-toggle-group v-for="size in ['small', 'normal', 'large']" :key="size" v-bind="args" :size="size" v-model="value" />
        </div>
    `),
    args: WithIcons.args,
};

export const Narrow: Story = {
    render: render(`
        <div style="inline-size: 320px; padding-inline: 16px; box-sizing: border-box; border: 1px dashed #ccc">
            <ui-toggle-group v-bind="args" v-model="value" class="w-full" />
        </div>
    `),
    args: {
        label: "Linie",
        modelValue: "lines",
        options: [
            { value: "lines", label: "Każda linia osobno", icon: "track" },
            { value: "combined", label: "Wszystkie razem", icon: "timetable" },
        ],
    },
};
