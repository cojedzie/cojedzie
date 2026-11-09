// @vitest-environment happy-dom
import { describe, expect, it } from "vitest";
import { mount } from "@vue/test-utils";
import UiToggleGroup from "@/components/ui/UiToggleGroup.vue";

const options = [
    { value: "list", label: "Lista", icon: "timetable" as const },
    { value: "hours", label: "Godziny" },
];

const global = { stubs: { UiIcon: { props: ["icon"], template: "<i class='icon' :data-icon='icon' />" } } };

describe("UiToggleGroup", () => {
    it("is a labelled group with the selected option pressed", () => {
        const wrapper = mount(UiToggleGroup, { props: { modelValue: "hours", options, label: "Widok" }, global });

        expect(wrapper.attributes("role")).toBe("group");
        expect(wrapper.attributes("aria-label")).toBe("Widok");
        expect(wrapper.findAll("button").map(button => [button.text(), button.attributes("aria-pressed")])).toEqual([
            ["Lista", "false"],
            ["Godziny", "true"],
        ]);
    });

    it("selects the option clicked", async () => {
        const wrapper = mount(UiToggleGroup, {
            props: {
                modelValue: "hours",
                options,
                label: "Widok",
                "onUpdate:modelValue": (value: string) => wrapper.setProps({ modelValue: value }),
            },
            global,
        });

        await wrapper.findAll("button")[0].trigger("click");

        expect(wrapper.emitted("update:modelValue")).toEqual([["list"]]);
        expect(wrapper.findAll("button").map(button => button.attributes("aria-pressed"))).toEqual(["true", "false"]);
    });

    it("shows the icon of an option that has one", () => {
        const wrapper = mount(UiToggleGroup, { props: { modelValue: "list", options, label: "Widok" }, global });

        const icons = wrapper.findAll("button").map(button => button.find(".icon"));

        expect(icons.map(icon => (icon.exists() ? icon.attributes("data-icon") : null))).toEqual(["timetable", null]);
    });
});
