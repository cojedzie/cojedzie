import UiSection from "./UiSection.vue";
import UiSectionSettings from "./UiSectionSettings.vue";
import UiIcon from "./UiIcon.vue";
import UiTooltip from "./UiTooltip.vue";
import { icons } from "@/icons";
import { Meta, StoryObj } from "@storybook/vue3-vite";

export default {
    title: "Ui/Section",
    component: UiSection,

    argTypes: {
        title: { control: { type: "text" } },
        icon: {
            options: [undefined, ...Object.keys(icons)],
            control: { type: "select" },
        },
        collapsible: { control: { type: "boolean" } },
        "onUpdate:collapsed": { action: "update:collapsed" },
    },

    args: {
        title: "Odjazdy",
        icon: "timetable",
    },
} satisfies Meta<typeof UiSection>;

type Story = StoryObj<typeof UiSection>;

const render =
    (template: string): Story["render"] =>
    args => ({
        components: { UiSection, UiSectionSettings, UiIcon, UiTooltip },
        setup: () => ({ args }),
        template,
    });

const content = `<p class="mb-0">Treść sekcji, np. lista odjazdów.</p>`;

export const Default: Story = {
    render: render(`<ui-section v-bind="args">${content}</ui-section>`),
};

export const TitleOnly: Story = {
    render: render(`<ui-section v-bind="args">${content}</ui-section>`),
    args: { icon: undefined },
};

export const CustomTitle: Story = {
    render: render(`
        <ui-section v-bind="args">
            <template #title="{ icon, title }">
                <ui-icon :icon="icon" fixed-width class="mr-1" />
                {{ title }}
                <span class="ml-2 badge badge-pill badge-dark">3</span>
            </template>
            ${content}
        </ui-section>
    `),
    args: { title: "Komunikaty", icon: "messages" },
};

export const WithActions: Story = {
    render: render(`
        <ui-section v-bind="args">
            <template #actions>
                <ui-section-settings>
                    <p class="mb-0 p-2">Ustawienia sekcji</p>
                </ui-section-settings>
                <button class="btn btn-action">
                    <ui-tooltip>odśwież</ui-tooltip>
                    <ui-icon icon="refresh" fixed-width />
                </button>
            </template>
            ${content}
        </ui-section>
    `),
};

export const Collapsible: Story = {
    render: render(`<ui-section v-bind="args">${content}</ui-section>`),
    args: { title: "Komunikaty", icon: "messages", collapsible: true },
};
