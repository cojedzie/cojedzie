<template>
    <article class="stop-timetable-page">
        <header class="header">
            <h1 class="title">
                <stop-label v-if="stop" :stop="stop" />
            </h1>
            <stop-destinations
                v-if="stop?.destinations?.length"
                :destinations="stop.destinations"
                wrap
                class="directions"
            />
            <nav class="lines" aria-label="Linie">
                <router-link :to="stopRoute" class="chip" :aria-current="lineId ? undefined : 'page'">
                    Wszystkie
                </router-link>
                <router-link
                    v-for="line in lines"
                    :key="line.id"
                    :to="lineRoute(line)"
                    class="chip"
                    :aria-current="line.id === lineId ? 'page' : undefined"
                >
                    <line-symbol :line="line" />
                </router-link>
            </nav>
            <router-link v-if="trackRoute" :to="trackRoute" class="route-link">
                <ui-icon icon="destination" />
                pokaż trasę
            </router-link>
        </header>

        <template v-if="ready">
            <p v-if="!range" class="no-timetable">Ten przewoźnik nie udostępnia rozkładu jazdy.</p>
            <p v-else-if="failed" class="failed">Nie udało się wczytać rozkładu jazdy.</p>
            <stop-timetable
                v-else
                v-model:date="date"
                v-model:view="view"
                v-model:grouping="settings.grouping"
                :departures="departures ?? []"
                :loading="departures === null"
                :time-zone="provider.timezone"
                :range="range"
                :line-fixed="!!lineId"
                :now="seenAt"
            />
        </template>
    </article>
</template>

<script setup lang="ts">
import { computed, ref, shallowRef, watch } from "vue";
import { type RouteLocationRaw, useRoute, useRouter } from "vue-router";
import { storeToRefs } from "pinia";
import api from "@/api";
import { convertTimetableRange, type Departure, type Line, type StopWithDestinations, type Track } from "@/model";
import {
    fetchStopTracks,
    fetchTimetableDay,
    linesOfTracks,
    timetableDateFromQuery,
    timetableDateQuery,
} from "@/timetable";
import { Temporal } from "@/time";
import { useToday } from "@/composables/useToday";
import { useSelectionStore } from "@/stores/selection";
import { useTimetableSettingsStore } from "@/stores/timetable-settings";
import LineSymbol from "@/components/line/LineSymbol.vue";
import StopDestinations from "@/components/stop/StopDestinations.vue";
import StopLabel from "@/components/stop/StopLabel.vue";
import StopTimetable from "@/components/timetable/StopTimetable.vue";
import UiIcon from "@/components/ui/UiIcon.vue";

defineOptions({ name: "StopTimetablePage" });

const route = useRoute();
const router = useRouter();
const today = useToday();
const settings = useTimetableSettingsStore();
const { provider } = storeToRefs(useSelectionStore());

const providerId = computed(() => route.params.provider as string);
const stopId = computed(() => route.params.stop as string);
const lineId = computed(() => route.params.line as string | undefined);

// the provider's details are loaded by the timetable's layout, nothing is asked of the API before they are
const ready = computed(() => provider.value?.id === providerId.value);
const range = computed(() => (provider.value?.timetable ? convertTimetableRange(provider.value.timetable) : null));

const date = computed({
    get: () => timetableDateFromQuery(route.query.date, today.value, range.value),
    set: date => {
        const query = { ...route.query };
        delete query.date;
        void router.push({ query: { ...query, ...timetableDateQuery(date, today.value) } });
    },
});

// until a view is picked, a stop's lines read best as a list, one line's day as hour rows, like the one printed at
// the stop; the view picked is remembered for both
const view = computed({
    get: () => settings.view ?? (lineId.value ? "hours" : "list"),
    set: view => settings.update({ view }),
});

const stop = shallowRef<StopWithDestinations | null>(null);
const tracks = shallowRef<Track[] | null>(null);

watch(
    [stopId, ready],
    async ([id, ready], _, onCleanup) => {
        let current = true;
        onCleanup(() => (current = false));

        stop.value = null;
        tracks.value = null;

        if (!ready) {
            return;
        }

        const [details, stopTracks] = await Promise.all([
            api.get("v1_stop_details", { version: "^1.2", params: { stop: id } }),
            fetchStopTracks(api, id),
        ]);

        if (current) {
            stop.value = details.data as StopWithDestinations;
            tracks.value = stopTracks;
        }
    },
    { immediate: true },
);

const lines = computed(() => linesOfTracks(tracks.value ?? []));

const departures = shallowRef<Departure[] | null>(null);
const failed = ref(false);
/** When the day shown was fetched, only when it is today: the timetable scrolls to that time */
const seenAt = shallowRef<Temporal.Instant | undefined>();

// dates and ranges are new objects whenever the route or the provider's details change, only another day refetches
watch(
    [stopId, lineId, () => date.value.toString(), tracks, () => range.value !== null],
    async ([stop, line, day, tracks, hasTimetable], _, onCleanup) => {
        let current = true;
        onCleanup(() => (current = false));

        departures.value = null;
        failed.value = false;

        if (!tracks || !hasTimetable) {
            return;
        }

        try {
            const shown = Temporal.PlainDate.from(day);
            const entries = await fetchTimetableDay(api, { stop, line, date: shown }, tracks);

            if (current) {
                departures.value = entries;
                seenAt.value = shown.equals(today.value) ? Temporal.Now.instant() : undefined;
            }
        } catch (error) {
            if (current) {
                console.error("Failed to fetch the timetable", error);
                failed.value = true;
            }
        }
    },
    { immediate: true },
);

// the chips switch between the whole stop and its lines on the same day
const stopRoute = computed((): RouteLocationRaw => ({
    name: "timetable-stop",
    params: { provider: providerId.value, stop: stopId.value },
    query: route.query,
}));

const lineRoute = (line: Line): RouteLocationRaw => ({
    name: "timetable-line-stop",
    params: { provider: providerId.value, line: line.id, stop: stopId.value },
    query: route.query,
});

// the line page (#157), on the line's track through this stop, once it exists
const trackRoute = computed((): RouteLocationRaw | null => {
    const track = tracks.value?.find(track => track.line.id === lineId.value);

    if (!track || !router.hasRoute("timetable-line")) {
        return null;
    }

    return {
        name: "timetable-line",
        params: { provider: providerId.value, line: track.line.id },
        query: { track: track.id },
    };
});
</script>

<style scoped>
@reference "@styles/tailwind.css";

.stop-timetable-page {
    @apply section-surface;
}

.header {
    display: flex;
    flex-direction: column;
    gap: --spacing(2);
}

.title {
    margin: 0;
    font-size: var(--text-2xl);
    font-weight: 600;
}

.directions {
    font-size: var(--text-sm);
}

.lines {
    display: flex;
    flex-wrap: wrap;
    gap: --spacing(1);
}

.chip {
    @apply focus-ring;
    display: inline-flex;
    align-items: center;
    min-block-size: --spacing(8);
    padding: --spacing(0.5) --spacing(3);
    border: 1px solid var(--color-divider);
    border-radius: var(--radius-full);
    color: inherit;
    text-decoration: none;

    &:hover {
        background: var(--color-hover);
    }

    &[aria-current="page"] {
        border-color: var(--color-primary);
        background: var(--color-hover-primary);
    }
}

.route-link {
    display: inline-flex;
    gap: --spacing(1);
    align-items: center;
    align-self: flex-start;
}

.no-timetable,
.failed {
    margin-block-start: --spacing(4);
    color: var(--color-muted);
}
</style>
