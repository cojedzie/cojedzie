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
                    <p v-for="error in errors.name" :key="error.name">
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
                <button class="btn btn-xs btn-action" @click="$emit('close')">anuluj</button>
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

<script lang="ts">
import { Stop } from "@/model";
import { Favourite } from "@/store/modules/favourites";
import * as uuid from "uuid";
import { Options, Vue } from "vue-class-component";
import { State } from "vuex-class";
import { Favourites } from "@/store";
import { Watch } from "vue-property-decorator";

function createFavouriteEntry(name: string, stops: Stop[]): Favourite {
    return {
        id: uuid.v4(),
        name,
        stops,
    };
}

@Options({ name: "FavouritesAdder" })
export default class FavouritesAdder extends Vue {
    @State stops: Stop[];

    private name = "";
    private errors = { name: [] };

    private confirmation = false;

    @Favourites.Mutation add: (favourite: Favourite) => void;

    @Watch("name")
    handleNameChange() {
        this.confirmation = false;
    }

    async save() {
        const favourite: Favourite = createFavouriteEntry(this.name, this.stops);

        if (this.validate(favourite)) {
            this.add(favourite);
            this.name = "";

            this.$emit("saved", favourite);
        }
    }

    private validate(favourite: Favourite) {
        const errors = { name: [] };

        if (favourite.name.length == 0) {
            errors.name.push("Musisz podać nazwę.");
        }

        if (
            this.$store.state.favourites.favourites.filter(other => other.name == favourite.name).length > 0 &&
            !this.confirmation
        ) {
            errors.name.push("Istnieje już zapisana grupa przystanków o takiej nazwie.");
            this.confirmation = true;
        }

        this.errors = errors;

        return (
            Object.entries(errors)
                .map(a => a[1])
                .reduce((acc, cur) => [...acc, ...cur]).length == 0
        );
    }
}
</script>
