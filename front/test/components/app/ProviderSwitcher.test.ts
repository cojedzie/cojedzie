// @vitest-environment happy-dom
import { beforeEach, describe, expect, it, vi } from "vitest";
import { ref } from "vue";
import { mount } from "@vue/test-utils";
import ProviderSwitcher from "@/components/app/ProviderSwitcher.vue";

const desktop = ref(false);
vi.mock("@vueuse/core", async importOriginal => ({
    ...(await importOriginal<typeof import("@vueuse/core")>()),
    useMediaQuery: () => desktop,
}));

const providers = [
    { id: "trojmiasto", name: "Trójmiasto" },
    { id: "warszawa", name: "Warszawa" },
] as any[];

function mountSwitcher(props: Partial<{ providers: any[] }> = {}) {
    return mount(ProviderSwitcher, {
        props: { current: { id: "trojmiasto", name: "Trójmiasto" }, providers, ...props },
        global: {
            stubs: {
                teleport: true,
                UiIcon: true,
                UiDialog: { template: '<div class="dialog"><slot /></div>' },
                UiSheet: { template: '<div class="sheet"><slot /></div>' },
            },
        },
    });
}

async function open(wrapper: ReturnType<typeof mountSwitcher>) {
    await wrapper.find(".chip").trigger("click");
}

describe("ProviderSwitcher", () => {
    beforeEach(() => {
        desktop.value = false;
    });

    it("shows the current provider", () => {
        expect(mountSwitcher().find(".chip").text()).toContain("Trójmiasto");
    });

    it("opens a sheet on mobile", async () => {
        const wrapper = mountSwitcher();
        await open(wrapper);

        expect(wrapper.find(".sheet").exists()).toBe(true);
        expect(wrapper.find(".dialog").exists()).toBe(false);
    });

    it("opens a dropdown on desktop", async () => {
        desktop.value = true;
        const wrapper = mountSwitcher();
        await open(wrapper);

        expect(wrapper.find(".dialog").exists()).toBe(true);
        expect(wrapper.find(".sheet").exists()).toBe(false);
    });

    it("lists the providers", async () => {
        const wrapper = mountSwitcher();
        await open(wrapper);

        const items = wrapper.findAll("[aria-current]");
        expect(items.map(item => item.text())).toEqual([
            expect.stringContaining("Trójmiasto"),
            expect.stringContaining("Warszawa"),
        ]);
    });

    it("marks the current provider", async () => {
        const wrapper = mountSwitcher();
        await open(wrapper);

        const items = wrapper.findAll("[aria-current]");
        expect(items.map(item => item.attributes("aria-current") === "true")).toEqual([true, false]);
    });

    it("emits the picked provider and closes", async () => {
        const wrapper = mountSwitcher();
        await open(wrapper);

        await wrapper.findAll("[aria-current]")[1].trigger("click");

        expect(wrapper.emitted("select")).toEqual([["warszawa"]]);
        expect(wrapper.find(".sheet").exists()).toBe(false);
    });

    it("says when there are no providers", async () => {
        const wrapper = mountSwitcher({ providers: [] });
        await open(wrapper);

        expect(wrapper.find("[aria-current]").exists()).toBe(false);
        expect(wrapper.find(".empty").exists()).toBe(true);
    });
});
