// @vitest-environment happy-dom
import { afterEach, describe, expect, it, vi } from "vitest";
import { flushPromises, mount } from "@vue/test-utils";
import { createPinia, setActivePinia } from "pinia";
import { createMemoryHistory, createRouter } from "vue-router";
import AppHeader from "@/components/app/AppHeader.vue";
import { navigation } from "@/navigation";
import { ContainerKey } from "@/services";
import { useSelectionStore } from "@/stores/selection";

const stubs = {
    teleport: true,
    UiIcon: true,
    UiDialog: true,
    AppMoreMenu: true,
    ProviderSwitcher: {
        props: ["current", "providers"],
        emits: ["select"],
        template: `<div class="switcher">
            <span class="switcher__name">{{ current.name }}</span>
            <button v-for="p in providers" :key="p.id" :class="'switcher__' + p.id" @click="$emit('select', p.id)" />
        </div>`,
    },
};

async function mountHeader(
    path = "/trojmiasto",
    getAllProviders = () => Promise.resolve([{ id: "trojmiasto" }, { id: "warszawa" }]),
) {
    const pinia = createPinia();
    setActivePinia(pinia);

    const page = { template: "<div />" };
    const router = createRouter({
        history: createMemoryHistory(),
        routes: [
            { path: "/", name: "providers", component: page },
            { path: "/:provider", name: "departures", component: page },
        ],
    });
    router.push(path);
    await router.isReady();

    const wrapper = mount(AppHeader, {
        global: {
            plugins: [pinia, router],
            provide: { [ContainerKey as symbol]: { get: () => ({ getAllProviders: vi.fn(getAllProviders) }) } },
            stubs,
        },
    });
    await flushPromises();

    return { wrapper, router, selection: useSelectionStore() };
}

// the observer the header watches its marker with, so a test can scroll it out of view
let observed: (entries: Partial<IntersectionObserverEntry>[]) => void;

class FakeIntersectionObserver {
    constructor(callback: typeof observed) {
        observed = callback;
    }
    observe() {}
    disconnect() {}
}

describe("AppHeader", () => {
    afterEach(() => vi.unstubAllGlobals());

    it("gets a shadow once it sticks to the top", async () => {
        vi.stubGlobal("IntersectionObserver", FakeIntersectionObserver);
        const { wrapper } = await mountHeader();
        const header = () => wrapper.find(".header");

        expect(header().classes()).not.toContain("stuck");

        observed([{ isIntersecting: false }]);
        await flushPromises();
        expect(header().classes()).toContain("stuck");

        observed([{ isIntersecting: true }]);
        await flushPromises();
        expect(header().classes()).not.toContain("stuck");
    });

    it("shows the route provider until the details load", async () => {
        const { wrapper } = await mountHeader();

        expect(wrapper.find(".switcher__name").text()).toBe("trojmiasto");
    });

    it("shows the provider's name once loaded", async () => {
        const { wrapper, selection } = await mountHeader();

        selection.provider = { id: "trojmiasto", name: "Trójmiasto" } as any;
        await flushPromises();

        expect(wrapper.find(".switcher__name").text()).toBe("Trójmiasto");
    });

    it("goes to the board of the picked provider", async () => {
        const { wrapper, router } = await mountHeader();

        await wrapper.find(".switcher__warszawa").trigger("click");
        await flushPromises();

        expect(router.currentRoute.value.fullPath).toBe("/warszawa");
    });

    it("still offers the switcher when the provider list fails to load", async () => {
        const { wrapper } = await mountHeader("/trojmiasto", () => Promise.reject(new Error("offline")));

        expect(wrapper.find(".switcher").exists()).toBe(true);
    });

    it("hides the switcher and menu on the provider chooser", async () => {
        const { wrapper } = await mountHeader("/");

        expect(wrapper.find(".switcher").exists()).toBe(false);
        expect(wrapper.find(".nav").exists()).toBe(false);
        expect(wrapper.find(".logo").attributes("href")).toBe("/");
    });

    it("links the menu entries to the provider", async () => {
        const { wrapper } = await mountHeader();

        const links = wrapper.findAll("a.link");
        expect(links).toHaveLength(navigation.length);
        for (const link of links) {
            expect(link.attributes("href")).toMatch(/^\/trojmiasto(\/|$)/);
        }
        expect(wrapper.find(".logo").attributes("href")).toBe("/trojmiasto");
    });

    it("names the icon-only more button", async () => {
        const { wrapper } = await mountHeader();

        expect(wrapper.find("button.link").attributes("aria-label")).toBeTruthy();
    });
});
