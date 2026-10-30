// @vitest-environment happy-dom
import { describe, expect, it } from "vitest";
import { mount } from "@vue/test-utils";
import StopLabel from "@/components/stop/StopLabel.vue";

const stop = { id: "1", name: "Brama Wyżynna", group: "Brama Wyżynna", $type: "vnd.cojedzie.stop" };

describe("StopLabel", () => {
    it("renders the variant when the stop has one", () => {
        const wrapper = mount(StopLabel, { props: { stop: { ...stop, variant: "02" } } });

        expect(wrapper.find(".stop__variant").text()).toBe("02");
    });
});
