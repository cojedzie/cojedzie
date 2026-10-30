<template>
    <div v-responsive class="departures">
        <ul class="departures__list list-underlined list-underlined--condensed">
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
