// @vitest-environment happy-dom
import { describe, expect, it, vi } from "vitest";
import { defineComponent, h, nextTick, ref, Transition } from "vue";
import { mount } from "@vue/test-utils";
import { useRemoved } from "@/composables/useRemoved";

function createChild(callback: () => void) {
    return defineComponent({
        props: { label: { type: String, default: "a" } },
        setup(props) {
            const root = ref<HTMLElement>();
            useRemoved(root, callback);

            return () => h("div", { ref: root }, props.label);
        },
    });
}

describe("useRemoved", () => {
    it("fires when the component is unmounted and its element leaves the document", async () => {
        const callback = vi.fn();
        const wrapper = mount(createChild(callback), { attachTo: document.body });

        expect(callback).not.toHaveBeenCalled();

        wrapper.unmount();
        await nextTick();

        expect(callback).toHaveBeenCalledTimes(1);
    });

    it("does not fire on a re-render", async () => {
        const callback = vi.fn();
        const wrapper = mount(createChild(callback), { attachTo: document.body });

        await wrapper.setProps({ label: "b" });
        await nextTick();

        expect(callback).not.toHaveBeenCalled();
        wrapper.unmount();
    });

    it("waits until the element is actually detached when it is kept for a leave transition", async () => {
        const callback = vi.fn();
        const Child = createChild(callback);
        let finishLeave: () => void = () => {};

        const Parent = defineComponent({
            props: { shown: Boolean },
            setup(props) {
                return () =>
                    h(
                        Transition,
                        { css: false, onLeave: (_: Element, done: () => void) => (finishLeave = done) },
                        { default: () => (props.shown ? h(Child) : null) },
                    );
            },
        });

        const wrapper = mount(Parent, {
            props: { shown: true },
            attachTo: document.body,
            global: { stubs: { transition: false } },
        });

        await wrapper.setProps({ shown: false });
        await new Promise(resolve => setTimeout(resolve));
        expect(callback).not.toHaveBeenCalled();

        finishLeave();
        await new Promise(resolve => setTimeout(resolve));
        expect(callback).toHaveBeenCalledTimes(1);

        wrapper.unmount();
    });
});
