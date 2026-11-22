// @vitest-environment happy-dom
import { afterEach, describe, expect, it } from "vitest";
import { mount } from "@vue/test-utils";
import { nextTick } from "vue";
import UiSectionSettings from "@/components/ui/UiSectionSettings.vue";

const UiDialog = { emits: ["leave"], template: "<div class='dialog' v-bind='$attrs'><slot /></div>" };
const global = { stubs: { UiIcon: true, UiTooltip: true, UiDialog, teleport: true } };

function mountSettings() {
    return mount(UiSectionSettings, {
        props: { label: "Ustawienia rozkładu" },
        slots: { default: "<button class='setting'>Lista</button>" },
        global,
        attachTo: document.body,
    });
}

describe("UiSectionSettings", () => {
    afterEach(() => (document.body.innerHTML = ""));

    it("opens its content in a dialog and closes it on leave", async () => {
        const wrapper = mountSettings();

        expect(wrapper.find(".setting").exists()).toBe(false);

        await wrapper.find("button").trigger("click");
        expect(wrapper.find(".dialog .setting").exists()).toBe(true);

        await wrapper.findComponent(UiDialog).vm.$emit("leave");
        expect(wrapper.find(".setting").exists()).toBe(false);
    });

    it("names the dialog after the button, and says the button opens one", async () => {
        const wrapper = mountSettings();
        const button = wrapper.find("button");

        expect(button.attributes("aria-haspopup")).toBe("dialog");

        await button.trigger("click");
        expect(wrapper.find(".dialog").attributes("aria-label")).toBe("Ustawienia rozkładu");
    });

    it("takes the keyboard into the dialog, and back to the button on Escape", async () => {
        const wrapper = mountSettings();
        const button = wrapper.find("button");

        await button.trigger("click");
        await nextTick();
        expect(document.activeElement).toBe(wrapper.find(".setting").element);

        document.activeElement!.dispatchEvent(new KeyboardEvent("keydown", { key: "Escape", bubbles: true }));
        await nextTick();

        expect(wrapper.find(".setting").exists()).toBe(false);
        expect(document.activeElement).toBe(button.element);
    });
});
