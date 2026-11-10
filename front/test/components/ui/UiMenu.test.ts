// @vitest-environment happy-dom
import { describe, expect, it } from "vitest";
import { mount } from "@vue/test-utils";
import UiMenu from "@/components/ui/UiMenu.vue";
import UiMenuItem from "@/components/ui/UiMenuItem.vue";

function mountMenu(template: string) {
    return mount({ components: { UiMenu, UiMenuItem }, template });
}

describe("UiMenu", () => {
    it("renders items as buttons by default", () => {
        const wrapper = mountMenu("<ui-menu><ui-menu-item>Tablica</ui-menu-item></ui-menu>");

        const item = wrapper.find("ul.menu > li > .item");
        expect(item.element.tagName).toBe("BUTTON");
        expect(item.attributes("type")).toBe("button");
        expect(item.text()).toBe("Tablica");
    });

    it("renders an item with the given tag", () => {
        const wrapper = mountMenu('<ui-menu><ui-menu-item tag="a" href="/api/doc">API</ui-menu-item></ui-menu>');

        const item = wrapper.find(".item");
        expect(item.element.tagName).toBe("A");
        expect(item.attributes("type")).toBeUndefined();
        expect(item.attributes("href")).toBe("/api/doc");
    });

    it("puts classes, attributes and listeners on the item, not the list element", async () => {
        const wrapper = mount({
            components: { UiMenu, UiMenuItem },
            data: () => ({ clicked: 0 }),
            template: `
                <ui-menu class="custom-menu">
                    <ui-menu-item class="custom-item" aria-current="true" @click="clicked++">x</ui-menu-item>
                </ui-menu>
            `,
        });

        expect(wrapper.find("ul").classes()).toContain("custom-menu");
        // apart from the scoping attribute of the menu's styles
        expect(Object.keys(wrapper.find("li").attributes()).filter(name => !name.startsWith("data-v-"))).toEqual([]);

        const item = wrapper.find(".item");
        expect(item.classes()).toContain("custom-item");
        expect(item.attributes("aria-current")).toBe("true");

        await item.trigger("click");
        expect(wrapper.vm.clicked).toBe(1);
    });
});
