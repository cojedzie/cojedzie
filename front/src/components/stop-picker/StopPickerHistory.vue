<template>
    <ul v-if="all.length > 0" class="history">
        <li v-for="(entry, index) in all" :key="index">
            <stop-picker-entry :stop="entry.stop">
                <template #primary-action>
                    <!-- the entry's main action, by default adding the stop to the departures board's selection -->
                    <slot name="primary-action" :stop="entry.stop">
                        <button @click="select(entry.stop)" class="btn btn-action stretched-link">
                            <ui-icon icon="history" />
                        </button>
                    </slot>
                </template>
            </stop-picker-entry>
        </li>
    </ul>
</template>

<script setup lang="ts">
import { storeToRefs } from "pinia";
import type { Stop } from "@/model";
import { useHistoryStore } from "@/stores/history";
import { useSelectionStore } from "@/stores/selection";
import StopPickerEntry from "./StopPickerEntry.vue";
import UiIcon from "@/components/ui/UiIcon.vue";

defineOptions({ name: "StopPickerHistory" });

const selection = useSelectionStore();
const { all } = storeToRefs(useHistoryStore());

defineSlots<{ "primary-action"?: (props: { stop: Stop }) => unknown }>();

function select(stop: Stop) {
    selection.add(stop);
}
</script>

<style scoped>
@reference "@styles/tailwind.css";

.history {
    @apply stop-entry-list;
}
</style>
