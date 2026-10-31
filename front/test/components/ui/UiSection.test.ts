// @vitest-environment happy-dom
import { describe, expect, it } from "vitest";
import { mount } from "@vue/test-utils";
import UiSection from "@/components/ui/UiSection.vue";

const UiFold = { props: ["visible"], template: "<div class='fold' :data-visible='visible'><slot /></div>" };
const global = { stubs: { UiIcon: true, UiTooltip: true, UiFold } };

describe("UiSection", () => {
    it("renders the title, actions and content", () => {
        const wrapper = mount(UiSection, {
            props: { title: "Odjazdy", icon: "timetable" },
            slots: { default: "<p class='content' />", actions: "<button class='action' />" },
            global,
        });

        expect(wrapper.find(".ui-section__title").text()).toBe("Odjazdy");
        expect(wrapper.find(".ui-section__actions .action").exists()).toBe(true);
        expect(wrapper.find(".content").exists()).toBe(true);
        expect(wrapper.find(".fold").exists()).toBe(false);
    });

    it("lets the title slot replace the icon and title", () => {
        const wrapper = mount(UiSection, {
            props: { title: "Komunikaty", icon: "messages" },
            slots: { title: "<template #title='{ title }'>{{ title }} (3)</template>" },
            global,
        });

        expect(wrapper.find(".ui-section__title").text()).toBe("Komunikaty (3)");
    });

    it("folds the content when collapsed and tells the binding", async () => {
        const wrapper = mount(UiSection, {
            props: { title: "Komunikaty", collapsible: true },
            slots: { default: "<p class='content' />" },
            global,
        });
        const toggle = wrapper.find(".ui-section__actions button");

        expect(wrapper.find(".fold").attributes("data-visible")).toBe("true");

        await toggle.trigger("click");

        expect(wrapper.find(".fold").attributes("data-visible")).toBe("false");
        expect(toggle.attributes("aria-expanded")).toBe("false");
        expect(wrapper.emitted("update:collapsed")).toEqual([[true]]);
    });
});
