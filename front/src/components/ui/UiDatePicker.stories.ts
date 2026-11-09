import UiDatePicker from "./UiDatePicker.vue";
import UiCalendar from "./UiCalendar.vue";
import { Meta, StoryObj } from "@storybook/vue3-vite";
import { shallowRef } from "vue";
import { Temporal } from "@/time";

const day = (date: string) => Temporal.PlainDate.from(date);

// stories fix today, so they look the same on any day
const today = day("2026-11-09");

export default {
    title: "Ui/DatePicker",
    component: UiDatePicker,

    argTypes: {
        variant: { control: { type: "inline-radio" }, options: ["normal", "primary"] },
        size: { control: { type: "inline-radio" }, options: ["small", "normal", "large"] },
        todayButton: { control: { type: "boolean" } },
        // dates are Temporal objects, which the controls can't edit
        modelValue: { control: false },
        today: { control: false },
        min: { control: false },
        max: { control: false },
        "onUpdate:modelValue": { action: "update:modelValue" },
    },

    args: {
        modelValue: today,
        today,
        min: day("2026-10-26"),
        max: day("2026-11-22"),
    },
} satisfies Meta<typeof UiDatePicker>;

type Story = StoryObj<typeof UiDatePicker>;

const render =
    (template = `<ui-date-picker v-bind="args" v-model="value" />`): Story["render"] =>
    args => ({
        components: { UiDatePicker, UiCalendar },
        setup: () => ({ args, value: shallowRef(args.modelValue) }),
        template,
    });

export const Today: Story = {
    render: render(),
};

export const AnotherDay: Story = {
    render: render(),
    args: { modelValue: day("2026-11-13") },
};

export const FirstDay: Story = {
    render: render(),
    args: { modelValue: day("2026-10-26") },
};

export const LastDay: Story = {
    render: render(),
    args: { modelValue: day("2026-11-22") },
};

export const WithoutTodayButton: Story = {
    render: render(),
    args: { modelValue: day("2026-11-13"), todayButton: false },
};

export const Primary: Story = {
    render: render(),
    args: { variant: "primary", modelValue: day("2026-11-13") },
};

export const Sizes: Story = {
    render: render(`
        <div style="display: flex; flex-direction: column; align-items: flex-start; gap: 1rem">
            <ui-date-picker v-for="size in ['small', 'normal', 'large']" :key="size" v-bind="args" :size="size" v-model="value" />
        </div>
    `),
    args: { modelValue: day("2026-11-13") },
};

export const Narrow: Story = {
    render: render(`
        <div style="inline-size: 260px; padding-inline: 16px; box-sizing: border-box; border: 1px dashed #ccc">
            <ui-date-picker v-bind="args" v-model="value" class="w-full" />
        </div>
    `),
    args: { modelValue: day("2026-11-13") },
};

export const Calendar: Story = {
    render: render(`<ui-calendar v-bind="args" v-model="value" />`),
    args: { modelValue: day("2026-11-13") },
};

export const CalendarFlat: Story = {
    render: render(`<ui-calendar v-bind="args" v-model="value" flat />`),
    args: { modelValue: day("2026-11-13") },
};

export const CalendarPrimary: Story = {
    render: render(`<ui-calendar v-bind="args" v-model="value" />`),
    args: { modelValue: day("2026-11-13"), variant: "primary" },
};
