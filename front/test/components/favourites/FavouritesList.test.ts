// @vitest-environment happy-dom
import { describe, expect, it } from "vitest";
import { nextTick } from "vue";
import { createPinia, setActivePinia } from "pinia";
import { mount } from "@vue/test-utils";
import FavouritesList from "@/components/favourites/FavouritesList.vue";
import { useFavouritesStore } from "@/stores/favourites";
import { useSelectionStore } from "@/stores/selection";

const stop = (id: string) => ({ id, name: `Stop ${id}`, group: "Group", $type: "vnd.cojedzie.stop" });

function mountList() {
    const pinia = createPinia();
    setActivePinia(pinia);

    const store = useFavouritesStore();
    store.add({ id: "a", name: "Home", stops: [stop("1")] });
    store.add({ id: "b", name: "Work", stops: [stop("2"), stop("3")] });

    const wrapper = mount(FavouritesList, {
        global: { plugins: [pinia], stubs: { StopLabel: true, UiIcon: true, UiTooltip: true } },
    });

    return { wrapper, store, selection: useSelectionStore() };
}

describe("FavouritesList", () => {
    it("replaces the selected stops with the chosen favourite's stops", async () => {
        const { wrapper, selection } = mountList();
        selection.replace([stop("9")]);

        await wrapper.findAll(".favourite__entry")[1].trigger("click");

        expect(selection.stops).toEqual([stop("2"), stop("3")]);
    });

    it("removes a favourite from the store", async () => {
        const { wrapper, store } = mountList();

        await wrapper.findAll(".favourite .btn-action")[0].trigger("click");

        expect(store.favourites.map(favourite => favourite.name)).toEqual(["Work"]);
    });

    it("tells when there is nothing saved", async () => {
        const { wrapper, store } = mountList();
        store.favourites = [];

        await nextTick();

        expect(wrapper.find(".alert").exists()).toBe(true);
    });
});
