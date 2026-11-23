<template>
    <div class="page-container timetable-page">
        <header class="header">
            <ui-breadcrumbs :crumbs="crumbs" />
            <!-- what the page is about: a line at a stop is the line's timetable, the stop is the column's heading -->
            <!-- on the search, its own section's title says it already -->
            <h1 class="heading" :class="{ 'visually-hidden': !onLineRoute && !route.params.stop }">
                <template v-if="onLineRoute">
                    Rozkład linii
                    <!-- at a stop, the line switches to the stop's other lines -->
                    <timetable-line-picker
                        v-if="headingLine && route.params.stop"
                        :line="headingLine"
                        :lines="stopLines"
                        :line-to="lineAtStop"
                        :all-to="wholeStop"
                    />
                    <line-symbol v-else-if="headingLine" :line="headingLine" size="large" />
                </template>
                <template v-else-if="route.params.stop">
                    Rozkład na
                    <stop-label v-if="headingStop" :stop="headingStop" />
                    <template v-else>przystanku</template>
                </template>
                <template v-else>Rozkłady jazdy</template>
            </h1>
            <!-- a whole stop's lines, under its name; a line at a stop has them under the stop in its column -->
            <stop-destinations
                v-if="!onLineRoute && headingStop?.destinations?.length"
                :destinations="headingStop.destinations"
                wrap
                class="destinations"
            />
        </header>
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
import { type RouteLocationRaw, useRoute } from "vue-router";
import type { Line, StopWithDestinations } from "@/model";
import { linesOfStop } from "@/timetable";
import { BREADCRUMB_HOLD_MS, useBreadcrumbs } from "@/composables/useBreadcrumbs";
import { useHeld } from "@/composables/useHeld";
import { useSelectionStore } from "@/stores/selection";
import LineSymbol from "@/components/line/LineSymbol.vue";
import StopDestinations from "@/components/stop/StopDestinations.vue";
import StopLabel from "@/components/stop/StopLabel.vue";
import TimetableLinePicker from "@/components/timetable/TimetableLinePicker.vue";
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
const stop = shallowRef<StopWithDestinations | null>(null);

const crumbs = useBreadcrumbs(() => ({ line: line.value, stop: stop.value }));

const onLineRoute = computed(() => route.params.line !== undefined);

// the heading names only the line or stop its route names, and like the crumbs keeps the previous one while the next
// loads, rather than flashing a placeholder between two names
const headingLine = useHeld(
    () => (line.value && line.value.id === route.params.line ? line.value : null),
    BREADCRUMB_HOLD_MS,
);
const headingStop = useHeld(
    () => (stop.value && stop.value.id === route.params.stop ? stop.value : null),
    BREADCRUMB_HOLD_MS,
);

// the line picker lists the open stop's lines once it is loaded, and switches between them on the same day
const loadedStop = computed(() => (stop.value?.id === route.params.stop ? stop.value : null));
const stopLines = computed(() => linesOfStop(loadedStop.value?.destinations ?? []));
const sameDay = computed(() => (route.query.date ? { date: route.query.date } : {}));

const lineAtStop = (line: Line): RouteLocationRaw => ({
    name: "timetable-line-stop",
    params: { provider: route.params.provider, line: line.id, stop: route.params.stop },
    query: sameDay.value,
});
const wholeStop = computed((): RouteLocationRaw => ({
    name: "timetable-stop",
    params: { provider: route.params.provider, stop: route.params.stop },
    query: sameDay.value,
}));
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

/* the trail and the heading above both columns, the heading's text lined up with the page */
.header {
    display: flex;
    flex-direction: column;
    margin-block-end: --spacing(6);
}

.heading {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: --spacing(3);
    margin: 0;
    font-size: var(--text-2xl);
    font-weight: var(--font-weight-medium);
    line-height: var(--leading-tight);

    @variant md {
        font-size: var(--text-3xl);
    }
}

.destinations {
    margin-block-start: --spacing(2);
    font-size: var(--text-sm);
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
