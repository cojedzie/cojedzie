<template>
    <p class="legend">
        <span v-for="item in legend" :key="item.note">
            <b class="note">{{ item.note }}</b>
            — {{ item.headsign }}
        </span>
    </p>
</template>

<script lang="ts" setup>
import type { Legend } from "@/timetable";

defineOptions({ name: "TimetableLegend" });

defineProps<{ legend: Legend }>();
</script>

<style scoped>
@reference "@styles/tailwind.css";

.legend {
    display: flex;
    flex-wrap: wrap;
    column-gap: --spacing(4);
    margin: --spacing(2) 0 0;
    color: var(--color-muted);
    font-size: var(--text-xs);
}

.note {
    color: var(--color-body);
}
</style>
