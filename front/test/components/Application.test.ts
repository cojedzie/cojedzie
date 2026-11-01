// @vitest-environment happy-dom
import { describe, expect, it } from "vitest";
import { flushPromises, mount } from "@vue/test-utils";
import { createMemoryHistory, createRouter } from "vue-router";
import Application from "@/components/Application.vue";

async function mountApp(path: string) {
    let mounts = 0;
    const page = { template: "<div class='page' />", mounted: () => mounts++ };
    const router = createRouter({
        history: createMemoryHistory(),
        routes: [
            { path: "/", name: "providers", component: page },
            { path: "/:provider", name: "departures", component: page },
        ],
    });
    router.push(path);
    await router.isReady();

    const wrapper = mount(Application, {
        global: {
            plugins: [router],
            stubs: {
                AppHeader: { template: "<header class='header' />" },
                AppFooter: { template: "<footer class='footer' />" },
                AppBottomBar: { props: ["provider"], template: "<nav class='bottom-bar'>{{ provider }}</nav>" },
            },
        },
    });

    return { wrapper, router, mounts: () => mounts };
}

describe("Application", () => {
    it("shows the bottom bar on a provider's pages", async () => {
        const { wrapper } = await mountApp("/trojmiasto");

        expect(wrapper.find(".bottom-bar").text()).toBe("trojmiasto");
    });

    it("hides the bottom bar on the provider chooser", async () => {
        const { wrapper } = await mountApp("/");

        expect(wrapper.find(".bottom-bar").exists()).toBe(false);
    });

    it("remounts the page when the provider changes", async () => {
        const { router, mounts } = await mountApp("/trojmiasto");

        await router.push("/warszawa");
        await flushPromises();

        expect(mounts()).toBe(2);
    });
});
