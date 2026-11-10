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
import api from "@/api";
import { useHistoryStore } from "@/stores/history";
import { type FoundStopGroup, searchStopGroups } from "@/components/stop-picker/search";
import StopPickerEntry from "@/components/stop-picker/StopPickerEntry.vue";
import StopPickerHistory from "@/components/stop-picker/StopPickerHistory.vue";
import TimetableStopLink from "@/components/timetable/TimetableStopLink.vue";
import UiIcon from "@/components/ui/UiIcon.vue";
import UiSection from "@/components/ui/UiSection.vue";

// the timetable's search: stops by name, opening a stop's timetable
defineOptions({ name: "TimetableStartPage" });

/** Stops are searched from this many characters, as on the departures page; below it the recent stops are shown. */
const MIN_STOP_QUERY = 3;
const DEBOUNCE = 400;

type Found = { query: string; groups: FoundStopGroup[]; failed: boolean };

const history = useHistoryStore();

const query = ref("");
/**
 * The stops found for a recent query, kept while a longer one is searched so they don't flicker away; `null` below
 * the threshold, until the first answer
 */
const found = shallowRef<Found | null>(null);

async function search(searched: string) {
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

    if (current.length < MIN_STOP_QUERY) {
        found.value = null;
        return;
    }

    timeout = setTimeout(() => search(current), DEBOUNCE);
});

onBeforeUnmount(() => clearTimeout(timeout));
</script>

<style scoped>
@reference "@styles/tailwind.css";

.stops {
    margin-block-start: --spacing(4);
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
