// @vitest-environment happy-dom
import { beforeEach, describe, expect, it } from "vitest";
import { createPinia, setActivePinia } from "pinia";
import { mount } from "@vue/test-utils";
import FavouritesAdder from "@/components/favourites/FavouritesAdder.vue";
import { useFavouritesStore } from "@/stores/favourites";
import { useSelectionStore } from "@/stores/selection";

const stop = { id: "1", name: "Brama Wyżynna", group: "Brama Wyżynna", $type: "vnd.cojedzie.stop" };

function mountAdder() {
    const pinia = createPinia();
    setActivePinia(pinia);

    useSelectionStore().replace([stop as any]);
    const wrapper = mount(FavouritesAdder, {
        global: {
            plugins: [pinia],
            stubs: { StopLabel: true, UiIcon: true },
            directives: { autofocus: {} },
        },
    });

    return { wrapper, store: useFavouritesStore() };
}

describe("FavouritesAdder", () => {
    let context: ReturnType<typeof mountAdder>;

    beforeEach(() => {
        context = mountAdder();
    });

    it("saves the selected stops under the given name", async () => {
        const { wrapper, store } = context;

        await wrapper.find("input").setValue("Home");
        await wrapper.find("form").trigger("submit");

        expect(store.favourites).toEqual([{ id: expect.any(String), name: "Home", stops: [stop] }]);
        expect(wrapper.emitted("saved")).toHaveLength(1);
    });

    it("shows an error and adds nothing when the name is empty", async () => {
        const { wrapper, store } = context;

        await wrapper.find("form").trigger("submit");

        expect(store.favourites).toEqual([]);
        expect(wrapper.find(".invalid-feedback").text()).toBe("Musisz podać nazwę.");
        expect(wrapper.emitted("saved")).toBeUndefined();
    });

    it("asks for confirmation before overwriting a favourite", async () => {
        const { wrapper, store } = context;
        store.add({ id: "a", name: "Home", stops: [] });

        await wrapper.find("input").setValue("Home");
        await wrapper.find("form").trigger("submit");

        expect(wrapper.find(".invalid-feedback").text()).toContain("Istnieje już");
        expect(wrapper.find("button.btn-danger").text()).toBe("nadpisz");
        expect(store.favourites[0].stops).toEqual([]);

        await wrapper.find("form").trigger("submit");

        expect(store.favourites).toEqual([{ id: "a", name: "Home", stops: [stop] }]);
    });
});
