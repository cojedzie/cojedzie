<template>
    <div class="departures">
        <ul class="list">
            <li v-for="departure in departures" :key="departure.key">
                <departures-departure :departure="departure" />
            </li>
        </ul>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { Departure } from "@/model";
import { useDeparturesStore } from "@/stores/departures";
import DeparturesDeparture from "./DeparturesDeparture.vue";

defineOptions({ name: "DeparturesList" });

const props = withDefaults(defineProps<{ departures?: Departure[] | null }>(), { departures: null });

const store = useDeparturesStore();
const departures = computed(() => props.departures || store.departures);
</script>

<style scoped>
@reference "@styles/tailwind.css";

/* departures lay themselves out by the list's width (container queries) */
.departures {
    container: departures / inline-size;

    & .list {
        @apply list-underlined list-underlined-condensed;
    }
}
</style>
