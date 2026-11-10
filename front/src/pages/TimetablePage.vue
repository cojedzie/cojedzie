<template>
    <div class="page-container timetable-page">
        <div class="columns" :class="{ 'with-main': !onStart }">
            <!-- the search on its own route, otherwise the route's side view (the line page, #157) -->
            <div class="column side">
                <timetable-start-page v-if="onStart" />
                <router-view v-else name="side" />
            </div>
            <div class="column main">
                <router-view />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useRoute } from "vue-router";
import { useSelectionStore } from "@/stores/selection";
import TimetableStartPage from "@/pages/TimetableStartPage.vue";

defineOptions({ name: "TimetablePage" });

const route = useRoute();
const selection = useSelectionStore();

// the timetable's days and hours are the provider's, its details are needed before anything is shown
void selection.loadProvider(route.params.provider as string);

const onStart = computed(() => route.name === "timetable");
</script>

<style scoped>
@reference "@styles/tailwind.css";

/* the departures page's layout: a third on the left, two thirds on the right; on a phone the pages stand alone */
.timetable-page {
    margin-block-start: --spacing(4);

    @variant md {
        padding-block-start: --spacing(8);
    }
}

.columns {
    display: flex;
    flex-wrap: wrap;
    margin-inline: calc(--spacing(4) * -1);
}

.column {
    inline-size: 100%;
    padding-inline: --spacing(4);
}

.side {
    @variant md {
        inline-size: calc(100% / 3);
    }
}

.main {
    @variant md {
        inline-size: calc(100% * 2 / 3);
    }
}

/* a phone shows the page on the right alone */
.with-main .side {
    @variant max-md {
        display: none;
    }
}
</style>
