<template>
    <div class="finder">
        <input
            class="form-control form-control--framed"
            :value="filter"
            placeholder="Zacznij pisać nazwę aby szukać..."
            @input="filter = ($event.target as HTMLInputElement).value"
        />

        <div v-if="filter.length < 3" class="mt-2">
            <favourites-list />
            <stop-picker-history />
        </div>

        <div v-if="state === 'fetching'" class="text-center p-4">
            <ui-icon icon="spinner" />
        </div>
        <div v-else-if="filter.length > 2 && Object.keys(filtered).length > 0" class="finder__stops">
            <div v-for="(group, name) in filtered" :key="name" class="stop-group">
                <div class="stop-group__header">
                    <h3 class="stop-group__name">
                        {{ name }}
                    </h3>

                    <div class="actions flex-space-left">
                        <button class="btn btn-action" @click="select(group)">
                            <ui-tooltip>wybierz wszystkie</ui-tooltip>
                            <ui-icon icon="add-all" />
                        </button>
                    </div>
                </div>
                <ul class="stop-group__stops list-underlined">
                    <li v-for="stop in group" :key="stop.id" class="d-flex">
                        <stop-picker-entry :stop="stop" class="flex-grow-1 finder__stop">
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
import moment from "moment";
import type { StopGroups, StopWithDestinations as Stop } from "@/model";
import { type FetchingState, filter as filterObject, map } from "@/utils";
import api from "@/api";
import { useHistoryStore } from "@/stores/history";
import FavouritesList from "@/components/favourites/FavouritesList.vue";
import StopPickerEntry from "./StopPickerEntry.vue";
import StopPickerHistory from "./StopPickerHistory.vue";

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
        const response = await api.get("v1_stop_group_list", {
            query: { name: filter.value, embed: "destinations" },
            version: "^1.0",
        });

        found.value = response.data.reduce(
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
    history.push({ date: moment(), stop: stop as Stop });

    emit("select", stop);
}
</script>
