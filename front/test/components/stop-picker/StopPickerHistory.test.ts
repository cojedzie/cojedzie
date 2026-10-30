// @vitest-environment happy-dom
import { describe, expect, it } from "vitest";
import { createPinia, setActivePinia } from "pinia";
import moment from "moment";
import { mount } from "@vue/test-utils";
import StopPickerHistory from "@/components/stop-picker/StopPickerHistory.vue";
import { useHistoryStore } from "@/stores/history";
import { useSelectionStore } from "@/stores/selection";

const stop = (id: string) => ({ id, name: `Stop ${id}`, group: "Group", $type: "vnd.cojedzie.stop" }) as any;

const entryStub = {
    props: ["stop"],
    template: '<div class="entry"><slot name="primary-action" />{{ stop.name }}</div>',
};

function mountHistory() {
    const pinia = createPinia();
    setActivePinia(pinia);

    const history = useHistoryStore();
    history.push({ stop: stop("1"), date: moment() });
    history.push({ stop: stop("2"), date: moment() });

    const wrapper = mount(StopPickerHistory, {
        global: { plugins: [pinia], stubs: { StopPickerEntry: entryStub, UiIcon: true } },
    });

    return { wrapper, selection: useSelectionStore() };
}

describe("StopPickerHistory", () => {
    it("lists history entries, newest first", () => {
        const { wrapper } = mountHistory();

        expect(wrapper.findAll(".entry").map(entry => entry.text())).toEqual(["Stop 2", "Stop 1"]);
    });

    it("adds the stop of a clicked entry to the selection", async () => {
        const { wrapper, selection } = mountHistory();
        selection.replace([stop("9")]);

        await wrapper.findAll("button")[1].trigger("click");

        expect(selection.stops.map(s => s.id)).toEqual(["9", "1"]);
    });
});
