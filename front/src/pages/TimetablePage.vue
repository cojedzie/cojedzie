<template>
    <div class="page-container timetable-page">
        <ui-breadcrumbs :crumbs="crumbs" class="trail" />
        <div class="columns" :class="{ 'with-main': stopOpen }">
            <!-- the route's side view (the line page), otherwise the search, kept across routes with its query -->
            <div class="column side">
                <router-view v-slot="{ Component }" name="side">
                    <component :is="Component" v-if="Component" @line="line = $event" />
                    <timetable-start-page v-else />
                </router-view>
            </div>
            <div class="column main">
                <router-view v-slot="{ Component }">
                    <component :is="Component" v-if="Component" @stop="stop = $event" />
                    <p v-else-if="onLine" class="hint">
                        Wybierz przystanek na trasie linii
                        <line-symbol v-if="line?.id === route.params.line" :line="line" />
                    </p>
                    <p v-else class="hint">Wybierz przystanek albo linię</p>
                </router-view>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, shallowRef } from "vue";
import { useRoute } from "vue-router";
import type { Line, Stop } from "@/model";
import { useBreadcrumbs } from "@/composables/useBreadcrumbs";
import { useSelectionStore } from "@/stores/selection";
import LineSymbol from "@/components/line/LineSymbol.vue";
import UiBreadcrumbs from "@/components/ui/UiBreadcrumbs.vue";
import TimetableStartPage from "@/pages/TimetableStartPage.vue";

defineOptions({ name: "TimetablePage" });

const route = useRoute();
const selection = useSelectionStore();

// the timetable's days and hours are the provider's, its details are needed before anything is shown
void selection.loadProvider(route.params.provider as string);

// on a phone a stop's timetable stands alone, otherwise the left column does
const stopOpen = computed(() => route.params.stop !== undefined);
const onLine = computed(() => route.name === "timetable-line");

/** The line the line page loaded, named by the hint and the breadcrumbs without fetching it again */
const line = shallowRef<Line | null>(null);
/** The stop the stop's timetable loaded, named by the breadcrumbs */
const stop = shallowRef<Stop | null>(null);

const crumbs = useBreadcrumbs(() => ({ line: line.value, stop: stop.value }));
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

.trail {
    margin-block-end: --spacing(4);
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

/* on a phone the left column stands alone, without the hint for the empty right column */
.hint {
    padding-block: --spacing(16);
    color: var(--color-muted);
    text-align: center;

    @variant max-md {
        display: none;
    }
}

/* a phone shows an open stop's timetable alone */
.with-main .side {
    @variant max-md {
        display: none;
    }
}
</style>
