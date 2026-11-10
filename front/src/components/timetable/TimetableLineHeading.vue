<template>
    <div class="heading" :style="{ '--line-color': `var(--color-line-${line.type})` }">
        <h3 class="symbol"><line-symbol :line="line" /></h3>
        <!-- where the line goes: its main headsign at the stop -->
        <span class="headsign"><slot /></span>
    </div>
</template>

<script lang="ts" setup>
import type { Line } from "@/model";
import LineSymbol from "@/components/line/LineSymbol.vue";

defineOptions({ name: "TimetableLineHeading" });

defineProps<{
    line: Line;
}>();
</script>

<style scoped>
@reference "@styles/tailwind.css";

/* a line's timetable at the stop: its symbol and where it goes, underlined in the line's colour */
.heading {
    display: flex;
    align-items: center;
    gap: --spacing(2);
    padding-block-end: --spacing(2);
    border-block-end: 2px solid var(--line-color, currentColor);
}

.symbol {
    margin: 0;
    font-size: var(--text-base);
    line-height: 1;
}

.headsign {
    min-inline-size: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
</style>
