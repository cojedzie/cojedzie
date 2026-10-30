<template>
    <div class="favourites">
        <ul v-if="favourites.length > 0" class="list-underlined">
            <li v-for="favourite in favourites" :key="favourite.id" class="favourite">
                <button class="favourite__entry" @click="choose(favourite)">
                    <div class="icon">
                        <ui-icon icon="favourite" />
                    </div>
                    <div class="overflow-hidden">
                        <span class="text flex-grow-1">{{ favourite.name }}</span>
                        <ul class="favourite__stops">
                            <li v-for="stop in favourite.stops" :key="stop.id" class="favourite__stop">
                                <stop-label :stop="stop" />
                            </li>
                        </ul>
                    </div>
                </button>
                <button class="btn btn-action" @click="remove(favourite)">
                    <ui-tooltip placement="left"> usuń </ui-tooltip>
                    <ui-icon icon="delete" />
                </button>
            </li>
        </ul>
        <div v-else class="alert alert-info">
            <ui-icon icon="info" />
            Brak zapisanych zespołów przystanków
        </div>
    </div>
</template>

<script setup lang="ts">
import { useStore } from "vuex";
import { storeToRefs } from "pinia";
import { type Favourite, useFavouritesStore } from "@/stores/favourites";

defineOptions({ name: "FavouritesList" });

const vuex = useStore();
const { favourites } = storeToRefs(useFavouritesStore());
const { remove } = useFavouritesStore();

function choose(favourite: Favourite) {
    vuex.commit("replace", favourite.stops);
}
</script>
