<template>
    <article class="stop-timetable-page">
        <!-- a whole stop is named by the page's heading, its lines under it; a line at a stop is the line's, the stop
             heads the column -->
        <header v-if="lineId" class="header">
            <h2 class="title">
                <stop-label v-if="stop" :stop="stop" />
            </h2>
            <div class="direction-row">
                <span v-if="direction" class="direction">Kierunek: {{ direction }}</span>
                <!-- on a phone the line page is hidden next to the stop: the way to it -->
                <router-link v-if="trackRoute" :to="trackRoute" class="route-link">
                    <ui-icon icon="destination" />
                    Trasa
                </router-link>
            </div>
            <stop-destinations
                v-if="stop?.destinations?.length"
                :destinations="stop.destinations"
                wrap
                class="directions"
            />
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
                :heading-level="lineId ? 3 : 2"
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
import { convertTimetableRange, type Departure, type StopWithDestinations, type Track } from "@/model";
import {
    chooseTrack,
    fetchStopTracks,
    fetchTimetableDay,
    timetableDateFromQuery,
    timetableDateQuery,
    trackDestination,
} from "@/timetable";
import { Temporal } from "@/time";
import { useToday } from "@/composables/useToday";
import { useSelectionStore } from "@/stores/selection";
import { useTimetableSettingsStore } from "@/stores/timetable-settings";
import StopDestinations from "@/components/stop/StopDestinations.vue";
import StopLabel from "@/components/stop/StopLabel.vue";
import StopTimetable from "@/components/timetable/StopTimetable.vue";
import UiIcon from "@/components/ui/UiIcon.vue";

defineOptions({ name: "StopTimetablePage" });

/** `stop`: the stop loaded, so the layout can name it */
const emit = defineEmits<{ stop: [stop: StopWithDestinations] }>();

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
            emit("stop", stop.value);
        }
    },
    { immediate: true },
);

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

// the line's track at this stop, as the line page shows it: the one in the URL when it runs through this stop,
// otherwise the line's first track through it, main before side; all the stop's tracks run through it
const track = computed(() => {
    const lineTracks = (tracks.value ?? []).filter(track => track.line.id === lineId.value);
    return chooseTrack(lineTracks, { query: route.query.track });
});

const direction = computed(() => (track.value ? trackDestination(track.value)?.name : undefined));

// the line page on that track
const trackRoute = computed((): RouteLocationRaw | null => {
    const shown = track.value;

    if (!shown || !router.hasRoute("timetable-line")) {
        return null;
    }

    return {
        name: "timetable-line",
        params: { provider: providerId.value, line: shown.line.id },
        query: { track: shown.id },
    };
});
</script>

<style scoped>
@reference "@styles/tailwind.css";

.stop-timetable-page {
    @apply section-surface;
}

/* as far from the timetable as the page's header is from the columns */
.header {
    display: flex;
    flex-direction: column;
    gap: --spacing(2);
    margin-block-end: --spacing(6);
}

.title {
    margin: 0;
    font-size: var(--text-2xl);
    font-weight: var(--font-weight-medium);
    line-height: var(--leading-tight);
}

.direction-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: --spacing(2);
    margin-block-start: calc(--spacing(2) * -1);
    font-size: var(--text-sm);
}

.direction {
    color: var(--color-muted);
}

.directions {
    font-size: var(--text-sm);
}

/* a 44px tap target, on a phone only: from md the line page is next to the stop */
.route-link {
    display: inline-flex;
    gap: --spacing(1);
    align-items: center;
    min-block-size: --spacing(11);
    margin-inline-start: auto;

    @variant md {
        display: none;
    }
}

.no-timetable,
.failed {
    margin-block-start: --spacing(4);
    color: var(--color-muted);
}
</style>
