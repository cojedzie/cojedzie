// @vitest-environment happy-dom
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { flushPromises, mount } from "@vue/test-utils";
import { createMemoryHistory, createRouter } from "vue-router";
import AppMoreMenu from "@/components/app/AppMoreMenu.vue";
import { useAboutLinks } from "@/composables/useAboutLinks";
import { routes } from "@/routes";
import type { NavigationEntry } from "@/navigation";

const entry: NavigationEntry = {
    id: "board",
    label: "Tablica",
    icon: "timetable",
    route: "departures",
    bottomBar: false,
};

async function mountMenu(entries: NavigationEntry[] = []) {
    const router = createRouter({ routes, history: createMemoryHistory() });
    router.push("/trojmiasto");
    await router.isReady();

    return mount(AppMoreMenu, {
        props: { entries, provider: "trojmiasto" },
        global: { plugins: [router], stubs: { UiIcon: true } },
    });
}

describe("AppMoreMenu", () => {
    beforeEach(() => vi.stubGlobal("CoJedzie", { version: "2026.11.0", production: false }));
    afterEach(() => vi.unstubAllGlobals());

    it("lists the about links", async () => {
        const wrapper = await mountMenu();

        const hrefs = wrapper.findAll(".app-more-menu__about a").map(link => link.attributes("href"));
        expect(hrefs).toEqual(useAboutLinks().links.map(link => link.href));
    });

    it("links the given entries to the provider's routes", async () => {
        const wrapper = await mountMenu([entry]);

        expect(wrapper.find(".app-more-menu__entry").attributes("href")).toBe("/trojmiasto");
    });

    it("emits navigate when a link is clicked", async () => {
        const wrapper = await mountMenu([entry]);

        await wrapper.find(".app-more-menu__entry").trigger("click");
        await wrapper.find(".app-more-menu__about a").trigger("click");
        await flushPromises();

        expect(wrapper.emitted("navigate")).toHaveLength(2);
    });
});
