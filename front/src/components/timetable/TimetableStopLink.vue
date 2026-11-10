<template>
    <router-link :to="to" class="btn btn-action stretched-link" @click="record">
        <ui-tooltip>rozkład jazdy</ui-tooltip>
        <ui-icon icon="timetable" />
    </router-link>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { RouteLocationRaw } from "vue-router";
import type { Stop } from "@/model";
import { useHistoryStore } from "@/stores/history";
import { Temporal } from "@/time";
import UiIcon from "@/components/ui/UiIcon.vue";
import UiTooltip from "@/components/ui/UiTooltip.vue";

// a stop's timetable as the timetable search links it; a stop chosen in the results becomes the most recent one,
// one chosen from the recent stops stays where it is, so the list doesn't jump under the rider's finger
defineOptions({ name: "TimetableStopLink" });

/** `remember`: push the stop to the history when it is opened */
const props = defineProps<{ stop: Stop; remember?: boolean }>();

const history = useHistoryStore();

/** A stop's timetable without a date, so a newly opened stop starts at today */
const to = computed<RouteLocationRaw>(() => ({ name: "timetable-stop", params: { stop: props.stop.id } }));

function record() {
    if (!props.remember) {
        return;
    }

    history.push({ stop: props.stop, date: Temporal.Now.instant() });
}
</script>
