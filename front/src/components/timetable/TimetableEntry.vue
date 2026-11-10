<template>
    <span class="times">
        <span class="planned" :class="{ superseded: entry.estimated }">{{ formatTime(entry.planned) }}</span>
        <sup v-if="note" class="note">{{ note }}</sup>
        <slot name="line" />
    </span>
    <span v-if="entry.estimated" class="estimate">
        <b>{{ formatTime(entry.estimated) }}</b>
        <span v-if="showsDelay" :class="['badge', delayBadge]">{{ signed(entry.delay) }}s</span>
    </span>
</template>

<script lang="ts" setup>
import { computed } from "vue";
import type { TimetableListEntry } from "@/timetable";
import { formatTime } from "@/time";
import { signed } from "@/utils";

defineOptions({ name: "TimetableEntry" });

const props = defineProps<{
    entry: TimetableListEntry;
    /** Footnote letter of the entry's headsign */
    note?: string;
}>();

defineSlots<{
    /** After the planned time, e.g. the line's symbol in the combined list */
    line?: () => unknown;
}>();

// as on the departure board: early by any amount, late by more than half a minute
const showsDelay = computed(() => props.entry.delay !== undefined && (props.entry.delay < 0 || props.entry.delay > 30));
const delayBadge = computed(() => (props.entry.delay < 0 ? "badge-danger" : "badge-warning"));
</script>

<style scoped>
@reference "@styles/tailwind.css";

/* the content of a timetable cell: the planned time, and for a live run its estimate and delay */
.times {
    display: flex;
    align-items: center;
    gap: --spacing(1);
    font-variant-numeric: tabular-nums;
}

.superseded {
    text-decoration: line-through;
}

.note {
    /* hairline: the letter sits against the time it belongs to */
    margin-inline-start: -2px;
    font-size: var(--text-xs);
    font-weight: bold;
}

.estimate {
    display: flex;
    align-items: center;
    gap: --spacing(1);
    font-size: var(--text-xs);
    font-variant-numeric: tabular-nums;
    line-height: var(--leading-tight);
}
</style>
