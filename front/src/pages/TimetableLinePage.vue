<template>
    <article class="line-page" :aria-busy="loading ? 'true' : undefined">
        <h2 class="title">Linia <line-symbol v-if="found" :line="found.line" /></h2>

        <div v-if="loading" class="loading">
            <ui-icon icon="spinner" />
        </div>
        <p v-else-if="failed" class="message">Nie udało się wczytać linii.</p>
        <p v-else-if="!found" class="message">Nie znaleziono linii.</p>
        <p v-else-if="found.tracks.length === 0" class="message">Ta linia nie ma żadnej trasy.</p>
        <template v-else-if="track">
            <track-direction-picker v-model="track" :tracks="found.tracks" class="direction" />
            <track-stop-rail :track="track" :current="stopId" />
        </template>
    </article>
</template>

<script setup lang="ts">
import { computed, ref, shallowRef, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { storeToRefs } from "pinia";
import api from "@/api";
import type { Line, Track } from "@/model";
import { chooseTrack, fetchLine, type LineWithTracks } from "@/timetable";
import { useSelectionStore } from "@/stores/selection";
import LineSymbol from "@/components/line/LineSymbol.vue";
import TrackDirectionPicker from "@/components/track/TrackDirectionPicker.vue";
import TrackStopRail from "@/components/track/TrackStopRail.vue";
import UiIcon from "@/components/ui/UiIcon.vue";

// a line's route: its tracks to pick from and the stops of the one picked, each opening the line's timetable there;
// the side view of a line and of a line at a stop, so it stays next to the stop opened
defineOptions({ name: "TimetableLinePage" });

/** `line`: the line loaded, so the layout can name it */
const emit = defineEmits<{ line: [line: Line] }>();

const route = useRoute();
const router = useRouter();
const { provider } = storeToRefs(useSelectionStore());

const providerId = computed(() => route.params.provider as string);
const lineId = computed(() => route.params.line as string | undefined);
const stopId = computed(() => route.params.stop as string | undefined);

// the provider's details are loaded by the timetable's layout, nothing is asked of the API before they are
const ready = computed(() => provider.value?.id === providerId.value);

/** The line and its tracks; `null` when there is no such line */
const found = shallowRef<LineWithTracks | null>(null);
const loading = ref(true);
const failed = ref(false);

watch(
    [lineId, ready],
    async ([id, ready], _, onCleanup) => {
        let current = true;
        onCleanup(() => (current = false));

        // the page is on its way out when the route has no line anymore
        if (!id) {
            return;
        }

        found.value = null;
        loading.value = true;
        failed.value = false;

        if (!ready) {
            return;
        }

        try {
            const line = await fetchLine(api, id);

            if (current) {
                found.value = line;
                loading.value = false;

                if (line) {
                    emit("line", line.line);
                }
            }
        } catch (error) {
            if (current) {
                console.error("Failed to fetch the line", error);
                failed.value = true;
                loading.value = false;
            }
        }
    },
    { immediate: true },
);

// a track picked replaces the URL's, so trying directions doesn't fill the history
const track = computed({
    get: () => chooseTrack(found.value?.tracks ?? [], { query: route.query.track, stop: stopId.value }),
    set: (track: Track | undefined) => {
        if (track) {
            void router.replace({ query: { ...route.query, track: track.id } });
        }
    },
});
</script>

<style scoped>
@reference "@styles/tailwind.css";

.line-page {
    @apply section-surface;
}

.title {
    display: flex;
    align-items: center;
    gap: --spacing(2);
    margin: 0;
    font-size: var(--text-2xl);
    font-weight: 600;
}

.direction {
    margin-block: --spacing(4) --spacing(2);
}

.loading {
    padding: --spacing(6);
    text-align: center;
}

.message {
    margin-block-start: --spacing(4);
    color: var(--color-muted);
}
</style>
