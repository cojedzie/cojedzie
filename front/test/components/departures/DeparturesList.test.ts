// @vitest-environment happy-dom
import { describe, expect, it } from "vitest";
import { createPinia, setActivePinia } from "pinia";
import { mount } from "@vue/test-utils";
import DeparturesList from "@/components/departures/DeparturesList.vue";
import { useDeparturesStore } from "@/stores/departures";

const departure = (key: string) => ({ key, display: `Departure ${key}` }) as any;

function mountList(props = {}) {
    const pinia = createPinia();
    setActivePinia(pinia);
    useDeparturesStore().departures = [departure("1"), departure("2")];

    return mount(DeparturesList, {
        props,
        global: {
            plugins: [pinia],
            directives: { responsive: {} },
            stubs: { DeparturesDeparture: { props: ["departure"], template: "<div>{{ departure.display }}</div>" } },
        },
    });
}

describe("DeparturesList", () => {
    it("renders the departures from the store", () => {
        const wrapper = mountList();

        expect(wrapper.findAll("li").map(item => item.text())).toEqual(["Departure 1", "Departure 2"]);
    });

    it("renders the given departures instead of the store's", () => {
        const wrapper = mountList({ departures: [departure("3")] });

        expect(wrapper.findAll("li").map(item => item.text())).toEqual(["Departure 3"]);
    });
});
