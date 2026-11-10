<template>
    <div class="finder">
        <input
            class="form-control form-control--framed"
            :value="filter"
            placeholder="Zacznij pisać nazwę aby szukać..."
            @input="filter = ($event.target as HTMLInputElement).value"
        />

        <div v-if="filter.length < 3" class="saved">
            <favourites-list />
            <stop-picker-history />
        </div>

        <div v-if="state === 'fetching'" class="loading">
            <ui-icon icon="spinner" />
        </div>
        <div v-else-if="filter.length > 2 && Object.keys(filtered).length > 0">
            <div v-for="(group, name) in filtered" :key="name" class="group">
                <div class="group-header">
                    <h3 class="group-name">
                        {{ name }}
                    </h3>

                    <div class="group-tools">
                        <button class="btn btn-action" @click="select(group)">
                            <ui-tooltip>wybierz wszystkie</ui-tooltip>
                            <ui-icon icon="add-all" />
                        </button>
                    </div>
                </div>
                <ul class="group-stops">
                    <li v-for="stop in group" :key="stop.id" class="group-stop">
                        <stop-picker-entry :stop="stop" class="entry">
                            <template #primary-action>
                                <button class="btn btn-action stretched-link" @click="select(stop)">
                                    <ui-tooltip>dodaj przystanek</ui-tooltip>
                                    <ui-icon icon="add" />
                                </button>
                            </template>
                        </stop-picker-entry>
                    </li>
                </ul>
            </div>
        </div>
        <div v-else-if="filter.length > 2" class="alert alert-warning">
            <ui-icon icon="warning" />
            Nie znaleziono więcej przystanków, spełniających te kryteria.
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from "vue";
import type { StopGroups, StopWithDestinations as Stop } from "@/model";
import { type FetchingState, filter as filterObject, map } from "@/utils";
import api from "@/api";
import { useHistoryStore } from "@/stores/history";
import FavouritesList from "@/components/favourites/FavouritesList.vue";
import StopPickerEntry from "./StopPickerEntry.vue";
import StopPickerHistory from "./StopPickerHistory.vue";
import { searchStopGroups } from "./search";
import UiIcon from "@/components/ui/UiIcon.vue";
import UiTooltip from "@/components/ui/UiTooltip.vue";
import { Temporal } from "@/time";

defineOptions({ name: "StopPicker" });

const props = withDefaults(defineProps<{ blacklist?: Stop[] }>(), { blacklist: () => [] });
const emit = defineEmits<{ select: [stop: Stop | Stop[]] }>();

const history = useHistoryStore();

const found = ref<StopGroups>({});
const state = ref<FetchingState>("ready");
const filter = ref("");

const filtered = computed<StopGroups>(() => {
    const groups = map(found.value, group =>
        group.filter(stop => !props.blacklist.some(blacklisted => blacklisted.id === stop.id)),
    ) as StopGroups;

    return filterObject(groups, group => group.length > 0);
});

async function fetch() {
    if (filter.value.length < 3) {
        return;
    }

    state.value = "fetching";
    try {
        const groups = await searchStopGroups(api, filter.value);

        found.value = groups.reduce(
            (accumulator, { name, stops }) => Object.assign(accumulator, { [name]: stops }),
            {},
        );
        state.value = "ready";
    } catch (error) {
        state.value = "error";
    }
}

let timeout: ReturnType<typeof setTimeout> | undefined;
watch(filter, () => {
    clearTimeout(timeout);
    timeout = setTimeout(fetch, 400);
});

onBeforeUnmount(() => clearTimeout(timeout));

function select(stop: Stop | Stop[]) {
    history.push({ date: Temporal.Now.instant(), stop: stop as Stop });

    emit("select", stop);
}
</script>

<style scoped>
@reference "@styles/tailwind.css";

.saved {
    margin-block-start: --spacing(2);
}

.loading {
    padding: --spacing(6);
    text-align: center;
}

.group:not(:last-child) {
    margin-block-end: --spacing(4);
}

.group-header {
    @apply ui-section-header;
    margin-block-end: 0;
}

.group-name {
    margin-block-end: 0;
    font-size: var(--text-base);
    font-weight: bold;
}

.group-tools {
    margin: calc(--spacing(2) * -1) calc(--spacing(3) * -1);
    margin-inline-start: auto;
}

.group-stops {
    @apply list-underlined;
}

.group-stop {
    display: flex;
}

.entry {
    flex-grow: 1;
    max-inline-size: 100%;
}
</style>
