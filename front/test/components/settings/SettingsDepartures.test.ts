// @vitest-environment happy-dom
import { describe, expect, it } from "vitest";
import { createPinia, setActivePinia } from "pinia";
import { mount } from "@vue/test-utils";
import SettingsDepartures from "@/components/settings/SettingsDepartures.vue";
import UiNumericInput from "@/components/ui/UiNumericInput.vue";
import UiSwitch from "@/components/ui/UiSwitch.vue";
import { useDeparturesSettingsStore } from "@/stores/departures-settings";

function mountSettings(initial: Parameters<ReturnType<typeof useDeparturesSettingsStore>["update"]>[0] = {}) {
    const pinia = createPinia();
    setActivePinia(pinia);

    const store = useDeparturesSettingsStore();
    store.update(initial);

    const slot = { template: '<div class="slot"><slot /></div>' };
    const wrapper = mount(SettingsDepartures, {
        global: {
            plugins: [pinia],
            components: { UiSwitch, UiNumericInput },
            stubs: { UiIcon: true, UiHelp: slot, UiFold: slot },
        },
    });

    return { wrapper, store };
}

describe("SettingsDepartures", () => {
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

    it("changes the number of displayed entries", async () => {
        const { wrapper, store } = mountSettings();

        const input = wrapper.find("input#departures-count");
        await input.setValue("12");
        await input.trigger("blur");

        expect(store.displayedEntriesCount).toBe(12);
    });

    it("toggles relative times", async () => {
        const { wrapper, store } = mountSettings();

        await wrapper.find("#departures-relative-times").trigger("click");

        expect(store.relativeTimes).toBe(true);
    });

    it("toggles relative times for scheduled departures", async () => {
        const { wrapper, store } = mountSettings();

        await wrapper.find("#departures-relative-times-for-scheduled").trigger("click");

        expect(store.relativeTimesForScheduled).toBe(false);
    });

    it("toggles the relative time limit and hides its input", async () => {
        const { wrapper, store } = mountSettings();
        expect(wrapper.find("#departures-max-relative-time").exists()).toBe(true);

        await wrapper.find("#departures-has-relative-time-limit").trigger("click");

        expect(store.relativeTimesLimitEnabled).toBe(false);
        expect(wrapper.find("#departures-max-relative-time").exists()).toBe(false);
    });

    it("changes the relative time limit", async () => {
        const { wrapper, store } = mountSettings();

        await wrapper.find("#departures-max-relative-time").setValue("25");

        expect(store.relativeTimesLimit).toBe(25);
    });
});
