<template>
    <div class="favourites">
        <ul v-if="favourites.length > 0" class="list">
            <li v-for="favourite in favourites" :key="favourite.id" class="favourite">
                <button class="btn btn-action entry" @click="choose(favourite)">
                    <div class="entry-icon">
                        <ui-icon icon="favourite" />
                    </div>
                    <div class="entry-text">
                        <span>{{ favourite.name }}</span>
                        <ul class="stops">
                            <li v-for="stop in favourite.stops" :key="stop.id">
                                <stop-label :stop="stop" />
                            </li>
                        </ul>
                    </div>
                </button>
                <button class="btn btn-action remove" @click="remove(favourite)">
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
import { storeToRefs } from "pinia";
import { type Favourite, useFavouritesStore } from "@/stores/favourites";
import { useSelectionStore } from "@/stores/selection";
import UiIcon from "@/components/ui/UiIcon.vue";
import UiTooltip from "@/components/ui/UiTooltip.vue";
import StopLabel from "@/components/stop/StopLabel.vue";

defineOptions({ name: "FavouritesList" });

const selection = useSelectionStore();
const { favourites } = storeToRefs(useFavouritesStore());
const { remove } = useFavouritesStore();

function choose(favourite: Favourite) {
    selection.replace(favourite.stops);
}
</script>

<style scoped>
@reference "@styles/tailwind.css";

.list {
    @apply list-underlined;
}

.favourite {
    display: flex;
}

.entry {
    display: flex;
    flex: 1 1 auto;
    align-items: center;
    overflow: hidden;
    padding: 0;
    text-align: start;
    text-overflow: ellipsis;
}

.entry-icon {
    padding: --spacing(2) --spacing(3);
}

.entry-text {
    overflow: hidden;
}

.stops {
    @apply stop-list-inline;
    max-inline-size: 100%;
    font-size: var(--text-xs);
}
</style>
