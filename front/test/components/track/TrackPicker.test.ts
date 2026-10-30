// @vitest-environment happy-dom
import { describe, expect, it, vi } from "vitest";
import { defineComponent } from "vue";
import { mount } from "@vue/test-utils";
import TrackPicker from "@/components/track/TrackPicker.vue";

const stopA = { id: "a", name: "Brama Wyżynna" };
const stopB = { id: "b", name: "Oliwa" };
const line = { id: "l", symbol: "6", type: "tram" };
const tracks = [
    { id: "t1", description: "Brama - Oliwa", line, stops: [stopA, stopB] },
    { id: "t2", description: "Oliwa - Brama", line, stops: [stopB, stopA] },
];

// renders the default slot for every option and lets the test pick one
const UiSelect = defineComponent({
    props: ["options", "modelValue"],
    emits: ["update:modelValue"],
    template: `<div class="ui-select">
        <button v-for="o in options" :key="o.id" class="option" @click="$emit('update:modelValue', o)">
            <slot :option="o" />
        </button>
    </div>`,
});

const mountPicker = (props: Record<string, unknown> = {}) =>
    mount(TrackPicker, {
        props: { tracks, ...props },
        global: {
            stubs: {
                UiSelect,
                LineSymbol: { props: ["line"], template: "<span class='line'>{{ line.symbol }}</span>" },
                StopLabel: { props: ["stop"], template: "<span class='stop'>{{ stop.name }}</span>" },
            },
        },
    });

describe("TrackPicker", () => {
    it("lists the tracks with their line, description and stops", () => {
        const options = mountPicker().findAll(".option");

        expect(options).toHaveLength(2);
        expect(options[0].find(".line").text()).toBe("6");
        expect(options[0].find(".track-picker__description").text()).toBe("Brama - Oliwa");
        expect(options[0].findAll(".stop").map(s => s.text())).toEqual(["Brama Wyżynna", "Oliwa"]);
    });

    it("emits the selected track", async () => {
        const onUpdate = vi.fn();
        const wrapper = mountPicker({ "onUpdate:modelValue": onUpdate });

        await wrapper.findAll(".option")[1].trigger("click");

        expect(onUpdate).toHaveBeenCalledWith(tracks[1]);
    });
});
