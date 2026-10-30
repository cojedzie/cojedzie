// @vitest-environment happy-dom
import { describe, expect, it, vi } from "vitest";
import { createPinia, setActivePinia } from "pinia";
import { createStore } from "vuex";
import moment from "moment";
import { mount } from "@vue/test-utils";
import StopPickerHistory from "@/components/stop-picker/StopPickerHistory.vue";
import { useHistoryStore } from "@/stores/history";

const stop = (id: string) => ({ id, name: `Stop ${id}`, group: "Group", $type: "vnd.cojedzie.stop" }) as any;

const entryStub = {
    props: ["stop"],
    template: '<div class="entry"><slot name="primary-action" />{{ stop.name }}</div>',
};

function mountHistory() {
    const pinia = createPinia();
    setActivePinia(pinia);

    const add = vi.fn();
    const vuex = createStore({ state: { stops: [] }, mutations: { add } });
    const history = useHistoryStore();
    history.push({ stop: stop("1"), date: moment() });
    history.push({ stop: stop("2"), date: moment() });

    const wrapper = mount(StopPickerHistory, {
        global: { plugins: [pinia, vuex], stubs: { StopPickerEntry: entryStub, UiIcon: true } },
    });

    return { wrapper, add };
}

describe("StopPickerHistory", () => {
    it("lists history entries, newest first", () => {
        const { wrapper } = mountHistory();

        expect(wrapper.findAll(".entry").map(entry => entry.text())).toEqual(["Stop 2", "Stop 1"]);
    });

    it("adds the stop of a clicked entry to the selection", async () => {
        const { wrapper, add } = mountHistory();

        await wrapper.findAll("button")[1].trigger("click");

        expect(add).toHaveBeenCalledWith(expect.anything(), stop("1"));
    });
});
