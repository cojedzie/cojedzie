<template>
    <form class="favourite-add-form" @submit.prevent="save">
        <div class="form-group">
            <label for="favourite_add_name">Nazwa</label>
            <div class="input-group">
                <input
                    id="favourite_add_name"
                    v-model="name"
                    v-autofocus
                    class="form-control form-control-sm"
                    placeholder="np. Z pracy"
                    :class="{ 'is-invalid': errors.name.length > 0 }"
                />
                <div v-if="errors.name.length > 0" class="invalid-feedback">
                    <p v-for="error in errors.name" :key="error">
                        {{ error }}
                    </p>
                </div>
            </div>
        </div>
        <ul class="favourite__stops">
            <li v-for="stop in stops" :key="stop.id" class="favourite__stop">
                <stop-label :stop="stop" />
            </li>
        </ul>
        <div class="favourite-add-form__actions">
            <template v-if="confirmation">
                <button class="btn btn-xs btn-danger" type="submit">nadpisz</button>
                <button class="btn btn-xs btn-action" @click="emit('close')">anuluj</button>
            </template>
            <template v-else>
                <button class="btn btn-xs btn-primary" type="submit">
                    <ui-icon icon="add" />
                    zapisz
                </button>
            </template>
        </div>
    </form>
</template>

<script setup lang="ts">
import { ref, watch } from "vue";
import { storeToRefs } from "pinia";
import * as uuid from "uuid";
import { type Favourite, useFavouritesStore } from "@/stores/favourites";
import { useSelectionStore } from "@/stores/selection";

defineOptions({ name: "FavouritesAdder" });

const emit = defineEmits<{
    saved: [favourite: Favourite];
    close: [];
}>();

const { favourites } = storeToRefs(useFavouritesStore());
const { add } = useFavouritesStore();

const { stops } = storeToRefs(useSelectionStore());

const name = ref("");
const errors = ref<{ name: string[] }>({ name: [] });
const confirmation = ref(false);

watch(name, () => {
    confirmation.value = false;
});

function validate(favourite: Favourite) {
    const result: { name: string[] } = { name: [] };

    if (favourite.name.length === 0) {
        result.name.push("Musisz podać nazwę.");
    }

    if (favourites.value.some(other => other.name === favourite.name) && !confirmation.value) {
        result.name.push("Istnieje już zapisana grupa przystanków o takiej nazwie.");
        confirmation.value = true;
    }

    errors.value = result;

    return result.name.length === 0;
}

function save() {
    const favourite: Favourite = { id: uuid.v4(), name: name.value, stops: stops.value };

    if (validate(favourite)) {
        add(favourite);
        name.value = "";

        emit("saved", favourite);
    }
}
</script>
