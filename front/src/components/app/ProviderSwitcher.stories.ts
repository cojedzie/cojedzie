import ProviderSwitcher from "./ProviderSwitcher.vue";
import { Meta, StoryObj } from "@storybook/vue3-vite";
import { Temporal } from "@/time";
import type { Provider } from "@/model";

const providers = [
    {
        id: "trojmiasto",
        name: "Trójmiasto",
        shortName: "ZTM",
        lastUpdate: Temporal.Now.instant().subtract({ hours: 3 }),
    },
    { id: "poznan", name: "Poznań", shortName: "ZTM", lastUpdate: Temporal.Now.instant().subtract({ hours: 24 }) },
    { id: "warszawa", name: "Warszawa", shortName: "WTP" },
] as Provider[];

export default {
    title: "App/ProviderSwitcher",
    component: ProviderSwitcher,

    args: {
        current: { id: "trojmiasto", name: "Trójmiasto" },
        providers,
    },
} satisfies Meta<typeof ProviderSwitcher>;

const render: StoryObj<typeof ProviderSwitcher>["render"] = args => ({
    components: { ProviderSwitcher },
    setup: () => ({ args }),
    template: `
        <div style="display: flex; align-items: center; padding: 0.5rem; background: var(--color-primary); color: white">
            <provider-switcher v-bind="args" />
        </div>
    `,
});

export const Default: StoryObj<typeof ProviderSwitcher> = { render };

export const NoProviders: StoryObj<typeof ProviderSwitcher> = { render, args: { providers: [] } };
