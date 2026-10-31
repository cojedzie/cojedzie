// @vitest-environment happy-dom
import { describe, expect, it } from "vitest";
import { mount } from "@vue/test-utils";
import UiSectionSettings from "@/components/ui/UiSectionSettings.vue";

const UiDialog = { emits: ["leave"], template: "<div class='dialog'><slot /></div>" };
const global = { stubs: { UiIcon: true, UiTooltip: true, UiDialog, teleport: true } };

describe("UiSectionSettings", () => {
    it("opens its content in a dialog and closes it on leave", async () => {
        const wrapper = mount(UiSectionSettings, { slots: { default: "<p class='settings' />" }, global });

        expect(wrapper.find(".settings").exists()).toBe(false);

        await wrapper.find("button").trigger("click");
        expect(wrapper.find(".dialog .settings").exists()).toBe(true);

        await wrapper.findComponent(UiDialog).vm.$emit("leave");
        expect(wrapper.find(".settings").exists()).toBe(false);
    });
});
