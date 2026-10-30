// @vitest-environment happy-dom
import { afterEach, describe, expect, it, vi } from "vitest";
import { defineComponent, h, nextTick } from "vue";
import { mount } from "@vue/test-utils";
import UiTooltip from "@/components/ui/UiTooltip.vue";

const UiDialog = defineComponent({
    props: ["placement", "reference", "arrow", "responsive"],
    template: "<div class='dialog'><slot /></div>",
});

function mountTooltip(props: Record<string, unknown> = {}) {
    const Host = defineComponent({
        setup: () => () =>
            h("button", { class: "anchor" }, [h(UiTooltip, { noTeleport: true, ...props }, () => "Tip")]),
    });

    return mount(Host, { attachTo: document.body, global: { stubs: { UiDialog, transition: false } } });
}

afterEach(() => {
    vi.useRealTimers();
    document.body.innerHTML = "";
});

describe("UiTooltip", () => {
    it("is hidden until its anchor is hovered for the delay", async () => {
        vi.useFakeTimers();
        const wrapper = mountTooltip({ delay: 100 });
        await nextTick();

        expect(wrapper.findComponent(UiDialog).exists()).toBe(false);

        await wrapper.find(".anchor").trigger("mouseenter");
        vi.advanceTimersByTime(99);
        await nextTick();
        expect(wrapper.findComponent(UiDialog).exists()).toBe(false);

        vi.advanceTimersByTime(1);
        await nextTick();
        expect(wrapper.findComponent(UiDialog).exists()).toBe(true);

        await wrapper.find(".anchor").trigger("mouseleave");
        expect(wrapper.findComponent(UiDialog).exists()).toBe(false);
    });

    it("shows on focus and hides on blur, with the given placement anchored to the parent", async () => {
        const wrapper = mountTooltip({ placement: "left" });
        await nextTick();

        await wrapper.find(".anchor").trigger("focus");

        const dialog = wrapper.findComponent(UiDialog);
        expect(dialog.exists()).toBe(true);
        expect(dialog.props("placement")).toBe("left");
        expect(dialog.props("reference")).toBe(wrapper.find(".anchor").element);
        expect(dialog.text()).toBe("Tip");

        await wrapper.find(".anchor").trigger("blur");
        expect(wrapper.findComponent(UiDialog).exists()).toBe(false);
    });

    it("stays visible when permanent", async () => {
        const wrapper = mountTooltip({ permanent: true });
        await nextTick();

        expect(wrapper.findComponent(UiDialog).exists()).toBe(true);
    });

    it("ignores triggers it was not given", async () => {
        const wrapper = mountTooltip({ triggers: ["hover"] });
        await nextTick();

        await wrapper.find(".anchor").trigger("focus");

        expect(wrapper.findComponent(UiDialog).exists()).toBe(false);
    });
});
