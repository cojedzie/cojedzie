// @vitest-environment happy-dom
import { describe, expect, it } from "vitest";
import { mount } from "@vue/test-utils";
import { createMemoryHistory, createRouter } from "vue-router";
import AppBottomBar from "@/components/app/AppBottomBar.vue";
import { navigation } from "@/navigation";

const stubs = {
    UiIcon: true,
    UiSheet: { template: '<div class="sheet"><slot /></div>' },
    AppMoreMenu: {
        props: ["entries", "provider"],
        emits: ["navigate"],
        template: '<button class="more-menu" @click="$emit(\'navigate\')">{{ entries.length }}</button>',
    },
};

async function mountBar() {
    const page = { template: "<div />" };
    const router = createRouter({
        history: createMemoryHistory(),
        routes: [
            { path: "/:provider", name: "departures", component: page },
            { path: "/:provider/timetable", name: "timetable", component: page },
        ],
    });
    router.push("/trojmiasto");
    await router.isReady();

    return mount(AppBottomBar, {
        props: { provider: "trojmiasto" },
        global: { plugins: [router], stubs, directives: { responsive: {} } },
    });
}

describe("AppBottomBar", () => {
    it("shows a named tab per bottom bar entry and the more button", async () => {
        const wrapper = await mountBar();

        const tabs = wrapper.findAll("a.tab");
        expect(tabs).toHaveLength(navigation.filter(entry => entry.bottomBar).length);
        for (const tab of [...tabs, wrapper.find(".more")]) {
            expect(tab.attributes("aria-label")).toBeTruthy();
        }
    });

    it("links the tabs to the provider's routes", async () => {
        const wrapper = await mountBar();

        expect(wrapper.find("a.tab").attributes("href")).toBe("/trojmiasto");
    });

    it("opens the more menu in a sheet", async () => {
        const wrapper = await mountBar();

        await wrapper.find("button.tab").trigger("click");

        expect(wrapper.find(".sheet .more-menu").exists()).toBe(true);
    });

    it("closes the sheet after navigating", async () => {
        const wrapper = await mountBar();
        await wrapper.find("button.tab").trigger("click");

        await wrapper.find(".more-menu").trigger("click");

        expect(wrapper.find(".sheet").exists()).toBe(false);
    });
});
