<template>
    <div class="list-per-line">
        <section v-for="list in lists" :key="list.line.id" class="line">
            <timetable-line-heading :line="list.line">{{ list.headsign }}</timetable-line-heading>
            <ol class="cells">
                <li
                    v-for="entry in list.entries"
                    :key="entry.departure.key"
                    class="cell"
                    :class="{ departed: entry.departed }"
                >
                    <timetable-entry :entry="entry" :note="entry.note" />
                </li>
            </ol>
            <timetable-legend v-if="list.legend.length > 0" :legend="list.legend" />
        </section>
    </div>
</template>

<script lang="ts" setup>
import type { LineList } from "@/timetable";
import TimetableEntry from "@/components/timetable/TimetableEntry.vue";
import TimetableLegend from "@/components/timetable/TimetableLegend.vue";
import TimetableLineHeading from "@/components/timetable/TimetableLineHeading.vue";

defineOptions({ name: "TimetableListPerLine" });

defineProps<{
    /** The day's entries, one list per line (`buildLineLists`) */
    lists: LineList[];
}>();
</script>

<style scoped>
@reference "@styles/tailwind.css";

.line {
    margin-block-start: --spacing(5);
}

/* dense on purpose: as many entries in a row as fit */
.cells {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(--spacing(18), 1fr));
    /* rows as tall as their cells, not all as tall as the tallest (the global reset's tracks) */
    grid-auto-rows: auto;
    column-gap: --spacing(1);
    margin: 0;
    padding: 0;
    list-style: none;
}

.cell {
    padding: --spacing(1) --spacing(2);
    /* hairline */
    border-block-end: 1px solid var(--color-divider);

    &.departed {
        opacity: var(--opacity-muted);
    }
}
</style>
