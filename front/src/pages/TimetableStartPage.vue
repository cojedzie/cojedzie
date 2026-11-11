<template>
    <ui-section icon="calendar" title="Rozkłady jazdy">
        <input
            class="form-control form-control--framed"
            type="search"
            :value="query"
            placeholder="Przystanek lub linia"
            aria-label="Przystanek lub linia"
            @input="query = ($event.target as HTMLInputElement).value"
        />

        <section v-if="lines.length > 0" class="lines" aria-label="Linie">
            <h3 class="heading">Linie</h3>
            <ul class="line-tracks">
                <li v-for="row in lines" :key="row.track?.id ?? row.line.id">
                    <component :is="hasLinePage ? RouterLink : 'div'" v-bind="lineLink(row)" class="line-track">
                        <line-symbol :line="row.line" />
                        <span v-if="row.track" class="ends">{{ trackEndsLabel(row.track) }}</span>
                    </component>
                </li>
            </ul>
        </section>

        <section v-if="query.length >= MIN_STOP_QUERY || history.all.length > 0" class="stops" aria-label="Przystanki">
            <template v-if="query.length < MIN_STOP_QUERY">
                <h3 class="heading">Ostatnio wybierane</h3>
                <stop-picker-history>
                    <template #primary-action="{ stop }">
                        <timetable-stop-link :stop="stop" />
                    </template>
                </stop-picker-history>
            </template>
            <div v-else-if="!found" class="loading">
                <ui-icon icon="spinner" />
            </div>
            <p v-else-if="found.failed" class="message">Nie udało się wyszukać przystanków.</p>
            <p v-else-if="found.groups.length === 0" class="message">Brak przystanków dla „{{ found.query }}”</p>
            <!-- a group's name is only a heading: timetables are of single stops -->
            <div v-for="group in found.groups" v-else :key="group.name" class="group">
                <h3 class="heading">{{ group.name }}</h3>
                <ul class="group-stops">
                    <li v-for="stop in group.stops" :key="stop.id">
                        <stop-picker-entry :stop="stop">
                            <template #primary-action>
                                <timetable-stop-link :stop="stop" remember />
                            </template>
                        </stop-picker-entry>
                    </li>
                </ul>
            </div>
        </section>
    </ui-section>
</template>

<script setup lang="ts">
import { onBeforeUnmount, ref, shallowRef, watch } from "vue";
import { RouterLink, useRouter } from "vue-router";
import api from "@/api";
import { useHistoryStore } from "@/stores/history";
import { type LineTrack, trackEndsLabel } from "@/timetable";
import { searchLineTracks } from "@/components/line/search";
import LineSymbol from "@/components/line/LineSymbol.vue";
import { type FoundStopGroup, searchStopGroups } from "@/components/stop-picker/search";
import StopPickerEntry from "@/components/stop-picker/StopPickerEntry.vue";
import StopPickerHistory from "@/components/stop-picker/StopPickerHistory.vue";
import TimetableStopLink from "@/components/timetable/TimetableStopLink.vue";
import UiIcon from "@/components/ui/UiIcon.vue";
import UiSection from "@/components/ui/UiSection.vue";

// the timetable's search: lines by symbol, opening a line's page on a main track, and stops by name, opening a stop's
// timetable
defineOptions({ name: "TimetableStartPage" });

/** Stops are searched from this many characters, as on the departures page; below it the recent stops are shown. */
const MIN_STOP_QUERY = 3;
/** Lines are searched from the first character, as short symbols ("2", "N") are whole ones. */
const MIN_LINE_QUERY = 1;
const DEBOUNCE = 400;

type Found = { query: string; groups: FoundStopGroup[]; failed: boolean };

const history = useHistoryStore();
const router = useRouter();

const query = ref("");
/**
 * The stops found for a recent query, kept while a longer one is searched so they don't flicker away; `null` below
 * the threshold, until the first answer
 */
const found = shallowRef<Found | null>(null);

/**
 * The main tracks of the lines found for a recent query, kept while the next one is searched; empty below the
 * threshold, until the first answer, or when the search failed
 */
const lines = shallowRef<LineTrack[]>([]);

async function searchLinesFor(searched: string) {
    let result: LineTrack[];
    try {
        result = await searchLineTracks(api, searched);
    } catch {
        result = [];
    }

    // a late answer to an older query is dropped
    if (searched === query.value) {
        lines.value = result;
    }
}

async function searchStopsFor(searched: string) {
    let result: Found;
    try {
        result = { query: searched, groups: await searchStopGroups(api, searched), failed: false };
    } catch {
        result = { query: searched, groups: [], failed: true };
    }

    // a late answer to an older query is dropped
    if (searched === query.value) {
        found.value = result;
    }
}

let timeout: ReturnType<typeof setTimeout> | undefined;
watch(query, current => {
    clearTimeout(timeout);

    if (current.length < MIN_LINE_QUERY) {
        lines.value = [];
    }

    if (current.length < MIN_STOP_QUERY) {
        found.value = null;
    }

    if (current.length >= MIN_LINE_QUERY) {
        timeout = setTimeout(() => {
            searchLinesFor(current);
            if (current.length >= MIN_STOP_QUERY) {
                searchStopsFor(current);
            }
        }, DEBOUNCE);
    }
});

onBeforeUnmount(() => clearTimeout(timeout));

// a found track opens its line's page on it, a line without a main track the page alone; a router without the page
// (Storybook's, a test's) gets the rows without links
const hasLinePage = router.hasRoute("timetable-line");

function lineLink({ line, track }: LineTrack) {
    if (!hasLinePage) {
        return {};
    }

    return { to: { name: "timetable-line", params: { line: line.id }, query: track ? { track: track.id } : {} } };
}
</script>

<style scoped>
@reference "@styles/tailwind.css";

.lines,
.stops {
    margin-block-start: --spacing(4);
}

.line-tracks {
    @apply list-underlined;

    & > li {
        padding: 0;
    }
}

/* a row like a stop's: the symbol, then where the track runs */
.line-track {
    display: flex;
    align-items: center;
    gap: --spacing(2);
    padding: --spacing(2) --spacing(1);
    color: inherit;
    text-decoration: none;

    &:is(a):hover {
        background: var(--color-hover-primary);
    }
}

.ends {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.heading {
    @apply list-header;
}

.loading {
    padding: --spacing(6);
    text-align: center;
}

.message {
    color: var(--color-muted);
}

.group:not(:last-child) {
    margin-block-end: --spacing(4);
}

.group-stops {
    @apply stop-entry-list;
}
</style>
