// @vitest-environment happy-dom
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { defineComponent, h, nextTick, ref } from "vue";
import { mount } from "@vue/test-utils";
import UiDialog from "@/components/ui/UiDialog.vue";

const popperInstances: { reference: unknown; element: unknown; destroy: () => void; update: () => void }[] = [];

vi.mock("popper.js", () => ({
    default: class {
        destroy = vi.fn();
        update = vi.fn();

        constructor(
            public reference: unknown,
            public element: unknown,
        ) {
            popperInstances.push(this);
        }
    },
}));

const stubs = { UiIcon: true };
let reference: HTMLElement;

beforeEach(() => {
    popperInstances.length = 0;
    reference = document.createElement("button");
    document.body.appendChild(reference);
});

afterEach(() => {
    document.body.innerHTML = "";
    document.body.className = "";
});

describe("UiDialog", () => {
    it("opens and closes with its host", async () => {
        const open = ref(true);
        const Host = defineComponent({
            setup: () => () => (open.value ? h(UiDialog, { reference, title: "Hello" }, () => "content") : null),
        });

        const wrapper = mount(Host, { attachTo: document.body, global: { stubs } });

        expect(wrapper.find("[role=dialog]").exists()).toBe(true);
        expect(wrapper.text()).toContain("Hello");
        expect(wrapper.text()).toContain("content");

        open.value = false;
        await nextTick();

        expect(wrapper.find("[role=dialog]").exists()).toBe(false);
        wrapper.unmount();
    });

    it("renders a modal that is closed by a click on the backdrop", async () => {
        const wrapper = mount(UiDialog, {
            props: { reference, behaviour: "modal", title: "Modal" },
            attachTo: document.body,
            global: { stubs },
        });

        expect(wrapper.find(".ui-modal").exists()).toBe(true);
        expect(document.body.classList.contains("contains-modal")).toBe(true);

        await wrapper.find(".ui-modal").trigger("click");
        expect(wrapper.emitted("leave")).toBeUndefined();

        await wrapper.find(".ui-backdrop").trigger("click");
        expect(wrapper.emitted("leave")).toHaveLength(1);

        wrapper.unmount();
        expect(document.body.classList.contains("contains-modal")).toBe(false);
    });

    it("emits leave and close from the close button", async () => {
        const wrapper = mount(UiDialog, {
            props: { reference, behaviour: "modal" },
            attachTo: document.body,
            global: { stubs },
        });

        await wrapper.find(".ui-modal__close").trigger("click");

        expect(wrapper.emitted("leave")).toHaveLength(1);
        expect(wrapper.emitted("close")).toHaveLength(1);
        wrapper.unmount();
    });

    it("emits leave when clicking outside of a popup", async () => {
        const wrapper = mount(UiDialog, { props: { reference }, attachTo: document.body, global: { stubs } });
        await nextTick();

        wrapper.element.dispatchEvent(new MouseEvent("click", { bubbles: true }));
        expect(wrapper.emitted("leave")).toBeUndefined();

        document.body.dispatchEvent(new MouseEvent("click", { bubbles: true }));
        expect(wrapper.emitted("leave")).toHaveLength(1);
        wrapper.unmount();
    });

    it("renders header and footer only when they have content", () => {
        const bare = mount(UiDialog, { props: { reference }, global: { stubs } });
        expect(bare.find(".ui-popup__header").exists()).toBe(false);
        expect(bare.find(".ui-popup__footer").exists()).toBe(false);

        const full = mount(UiDialog, {
            props: { reference },
            slots: { title: "Custom title", footer: "Footer" },
            global: { stubs },
        });
        expect(full.find(".ui-popup__header").text()).toBe("Custom title");
        expect(full.find(".ui-popup__footer").text()).toBe("Footer");
    });

    it("destroys its popper when the reference element disappears with it", async () => {
        const open = ref(true);
        const Host = defineComponent({
            setup: () => () => (open.value ? h(UiDialog, { reference }) : null),
        });

        const wrapper = mount(Host, { attachTo: document.body, global: { stubs } });
        expect(popperInstances).toHaveLength(1);

        open.value = false;
        await nextTick();

        expect(popperInstances[0].destroy).toHaveBeenCalled();
        wrapper.unmount();
    });
});
