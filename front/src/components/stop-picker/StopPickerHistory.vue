<template>
    <ul class="list-underlined history" v-if="all.length > 0">
        <li v-for="(entry, index) in all" :key="index" class="history__entry">
            <stop-picker-entry :stop="entry.stop" class="flex-grow-1 finder__stop">
                <template #primary-action>
                    <button @click="select(entry.stop)" class="btn btn-action stretched-link">
                        <ui-icon icon="history" />
                    </button>
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

function select(stop: Stop) {
    selection.add(stop);
}
</script>
