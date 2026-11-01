// @vitest-environment happy-dom
import { afterEach, describe, expect, it } from "vitest";
import { mount } from "@vue/test-utils";
import UiSheet from "@/components/ui/UiSheet.vue";

function mountSheet(title?: string) {
    return mount(UiSheet, {
        props: { title },
        slots: { default: '<button class="content">content</button>' },
        attachTo: document.body,
        global: { stubs: { teleport: true } },
    });
}

afterEach(() => {
    document.body.innerHTML = "";
});

describe("UiSheet", () => {
    it("renders the title and content in a dialog", () => {
        const wrapper = mountSheet("title");

        expect(wrapper.find("[role=dialog]").text()).toContain("title");
        expect(wrapper.find("[role=dialog] .content").exists()).toBe(true);
    });

    it("leaves when the backdrop is clicked", async () => {
        const wrapper = mountSheet();

        await wrapper.find(".ui-sheet").trigger("click");

        expect(wrapper.emitted("leave")).toHaveLength(1);
    });

    it("leaves on Escape", async () => {
        const wrapper = mountSheet();

        document.dispatchEvent(new KeyboardEvent("keydown", { key: "Escape" }));

        expect(wrapper.emitted("leave")).toHaveLength(1);
    });

    it("stays open when the content is clicked", async () => {
        const wrapper = mountSheet();

        await wrapper.find(".content").trigger("click");

        expect(wrapper.emitted("leave")).toBeUndefined();
    });
});
