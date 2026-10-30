// @vitest-environment happy-dom
import { describe, expect, it } from "vitest";
import { mount } from "@vue/test-utils";
import { IconDefinition } from "@fortawesome/fontawesome-svg-core";
import UiIcon from "@/components/ui/UiIcon.vue";

const custom = { prefix: "fas", iconName: "custom", icon: [1, 1, [], "f000", ""] } as unknown as IconDefinition;
const Fa = { props: ["icon", "transform", "fixedWidth"], template: "<i class='fa' />" };
const FaLayers = { template: "<span class='fa-layers'><slot /></span>" };
const global = { stubs: { FontAwesomeIcon: Fa, FontAwesomeLayers: FaLayers } };

describe("UiIcon", () => {
    it("renders a stacked icon as layers", () => {
        const wrapper = mount(UiIcon, { props: { icon: "relative-time-limit" }, global });

        expect(wrapper.find(".fa-layers").classes()).toContain("ui-icon");
        expect(wrapper.findAllComponents(Fa)).toHaveLength(2);
    });

    it("renders a custom icon definition", () => {
        const wrapper = mount(UiIcon, { props: { icon: custom }, global });

        expect(wrapper.findComponent(Fa).props("icon")).toEqual(custom);
    });
});
