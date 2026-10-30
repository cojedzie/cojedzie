// @vitest-environment happy-dom
import { beforeEach, describe, expect, it, vi } from "vitest";
import { flushPromises, mount } from "@vue/test-utils";
import moment from "moment";
import ProviderChooserPage from "@/pages/ProviderChooserPage.vue";
import { ContainerKey } from "@/services";

const providers = [
    { id: "trojmiasto", shortName: "ZTM", name: "Trójmiasto", lastUpdate: moment("2024-05-01T10:30:00") },
    { id: "warszawa", shortName: "WTP", name: "Warszawa", lastUpdate: null },
];

const getAllProviders = vi.fn();

async function mountPage() {
    const wrapper = mount(ProviderChooserPage, {
        global: {
            provide: { [ContainerKey as symbol]: { get: () => ({ getAllProviders }) } },
            stubs: {
                UiMap: { template: '<div class="ui-map"><slot /></div>' },
                LMarker: true,
                LIcon: true,
                UiIcon: true,
                UiTooltip: { template: '<span class="tooltip"><slot /></span>' },
            },
        },
    });
    await flushPromises();

    return wrapper;
}

describe("ProviderChooserPage", () => {
    beforeEach(() => {
        getAllProviders.mockReset();
        getAllProviders.mockResolvedValue(providers);
        document.head.innerHTML = '<link rel="manifest" href="/old.json">';
    });

    it("lists the providers", async () => {
        const wrapper = await mountPage();

        const entries = wrapper.findAll(".provider-picker__provider");
        expect(entries.map(entry => entry.find(".provider__short-name").text())).toEqual(["ZTM", "WTP"]);
        expect(entries.map(entry => entry.find(".provider__name").text())).toEqual(["Trójmiasto", "Warszawa"]);
    });

    it("links each provider to its route", async () => {
        const wrapper = await mountPage();

        const links = wrapper.findAll(".provider-picker__provider a");
        expect(links.map(link => link.attributes("href"))).toEqual(["/trojmiasto", "/warszawa"]);
    });

    it("shows when the provider was last updated", async () => {
        const wrapper = await mountPage();

        const entries = wrapper.findAll(".provider-picker__provider");
        expect(entries[0].find(".tooltip").text()).toContain("2024-05-01 10:30");
        expect(entries[1].find(".tooltip").exists()).toBe(false);
    });

    it("points the manifest at the default one", async () => {
        await mountPage();

        expect(document.querySelector<HTMLLinkElement>('link[rel="manifest"]').getAttribute("href")).toBe(
            "/manifest.json",
        );
    });
});
