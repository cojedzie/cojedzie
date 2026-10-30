// @vitest-environment happy-dom
import { afterEach, describe, expect, it } from "vitest";
import { defineComponent } from "vue";
import { mount } from "@vue/test-utils";
import UiHelp from "@/components/ui/UiHelp.vue";
import { createMutex } from "@/composables/useMutex";

const UiDialog = defineComponent({
    props: ["reference", "placement", "mobileBehaviour"],
    template: "<div class='dialog'><div class='dialog-title'><slot name='title' /></div><slot /></div>",
});

function mountHelp(props: Record<string, unknown> = {}) {
    const popups = document.createElement("div");
    popups.id = "popups";
    document.body.appendChild(popups);

    return mount(UiHelp, {
        props: { mutex: createMutex(), ...props },
        slots: { default: "Help content", title: "Help title" },
        attachTo: document.body,
        global: { stubs: { UiDialog, UiIcon: true } },
    });
}

afterEach(() => {
    document.body.innerHTML = "";
});

describe("UiHelp", () => {
    it("shows its content in a dialog next to the button after a click", async () => {
        const wrapper = mountHelp();

        expect(document.querySelector(".dialog")).toBeNull();

        await wrapper.find("button").trigger("click");

        const dialog = document.querySelector("#popups .dialog");
        expect(dialog?.textContent).toContain("Help content");
        expect(dialog?.querySelector(".dialog-title")?.textContent).toBe("Help title");
        expect(wrapper.findComponent(UiDialog).props("reference")).toBe(wrapper.find("button").element);
    });

    it("hides the dialog on a second click", async () => {
        const wrapper = mountHelp();

        await wrapper.find("button").trigger("click");
        await wrapper.find("button").trigger("click");

        expect(document.querySelector(".dialog")).toBeNull();
    });

    it("lets only one help be open at a time when sharing a mutex", async () => {
        const mutex = createMutex();
        const first = mountHelp({ mutex });
        const second = mountHelp({ mutex });

        await first.find("button").trigger("click");
        await second.find("button").trigger("click");

        expect(document.querySelectorAll(".dialog")).toHaveLength(1);
    });
});
