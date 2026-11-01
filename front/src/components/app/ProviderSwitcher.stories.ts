import ProviderSwitcher from "./ProviderSwitcher.vue";
import { Meta, StoryObj } from "@storybook/vue3-vite";
import moment from "moment";
import type { Provider } from "@/model";

const providers = [
    { id: "trojmiasto", name: "Trójmiasto", shortName: "ZTM", lastUpdate: moment().subtract(3, "hours") },
    { id: "poznan", name: "Poznań", shortName: "ZTM", lastUpdate: moment().subtract(1, "day") },
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
        <div class="hstack p-2 bg-primary text-white">
            <provider-switcher v-bind="args" />
        </div>
    `,
});

export const Default: StoryObj<typeof ProviderSwitcher> = { render };

export const NoProviders: StoryObj<typeof ProviderSwitcher> = { render, args: { providers: [] } };
