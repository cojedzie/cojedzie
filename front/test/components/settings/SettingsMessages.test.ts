// @vitest-environment happy-dom
import { describe, expect, it } from "vitest";
import { createPinia, setActivePinia } from "pinia";
import { mount } from "@vue/test-utils";
import SettingsMessages from "@/components/settings/SettingsMessages.vue";
import UiNumericInput from "@/components/ui/UiNumericInput.vue";
import UiSwitch from "@/components/ui/UiSwitch.vue";
import { useMessagesSettingsStore } from "@/stores/messages-settings";

function mountSettings() {
    const pinia = createPinia();
    setActivePinia(pinia);

    const wrapper = mount(SettingsMessages, {
        global: {
            plugins: [pinia],
            components: { UiSwitch, UiNumericInput },
            stubs: { UiIcon: true, UiHelp: { template: '<div class="help"><slot /></div>' } },
        },
    });

    return { wrapper, store: useMessagesSettingsStore() };
}

describe("SettingsMessages", () => {
    it("toggles auto-refresh", async () => {
        const { wrapper, store } = mountSettings();

        await wrapper.find("#departures-auto-refresh").trigger("click");

        expect(store.autorefresh).toBe(false);
        expect(wrapper.find("#departures-auto-refresh-interval").exists()).toBe(false);
    });

    it("changes the refresh interval", async () => {
        const { wrapper, store } = mountSettings();

        await wrapper.find("#departures-auto-refresh-interval").setValue("30");

        expect(store.autorefreshInterval).toBe(30);
    });

    it("changes the number of displayed messages", async () => {
        const { wrapper, store } = mountSettings();

        const input = wrapper.find("input#departures-count");
        await input.setValue("7");
        await input.trigger("blur");

        expect(store.displayedEntriesCount).toBe(7);
    });
});
