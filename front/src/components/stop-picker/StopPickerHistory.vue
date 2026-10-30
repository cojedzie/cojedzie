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
import { useStore } from "vuex";
import { storeToRefs } from "pinia";
import type { Stop } from "@/model";
import { useHistoryStore } from "@/stores/history";
import StopPickerEntry from "./StopPickerEntry.vue";

defineOptions({ name: "StopPickerHistory" });

const vuex = useStore();
const { all } = storeToRefs(useHistoryStore());

function select(stop: Stop) {
    vuex.commit("add", stop);
}
</script>
