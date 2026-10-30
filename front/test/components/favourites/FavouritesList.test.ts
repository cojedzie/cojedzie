// @vitest-environment happy-dom
import { describe, expect, it, vi } from "vitest";
import { nextTick } from "vue";
import { createPinia, setActivePinia } from "pinia";
import { createStore } from "vuex";
import { mount } from "@vue/test-utils";
import FavouritesList from "@/components/favourites/FavouritesList.vue";
import { useFavouritesStore } from "@/stores/favourites";

const stop = (id: string) => ({ id, name: `Stop ${id}`, group: "Group", $type: "vnd.cojedzie.stop" });

function mountList() {
    const pinia = createPinia();
    setActivePinia(pinia);

    const replace = vi.fn();
    const vuex = createStore({ state: { stops: [] }, mutations: { replace } });
    const store = useFavouritesStore();
    store.add({ id: "a", name: "Home", stops: [stop("1")] });
    store.add({ id: "b", name: "Work", stops: [stop("2"), stop("3")] });

    const wrapper = mount(FavouritesList, {
        global: { plugins: [pinia, vuex], stubs: { StopLabel: true, UiIcon: true, UiTooltip: true } },
    });

    return { wrapper, store, replace };
}

describe("FavouritesList", () => {
    it("replaces the selected stops with the chosen favourite's stops", async () => {
        const { wrapper, replace } = mountList();

        await wrapper.findAll(".favourite__entry")[1].trigger("click");

        expect(replace).toHaveBeenCalledWith(expect.anything(), [stop("2"), stop("3")]);
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
