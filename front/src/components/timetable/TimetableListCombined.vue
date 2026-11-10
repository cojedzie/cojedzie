<template>
    <ol class="cells">
        <li
            v-for="entry in entries"
            :key="entry.departure.key"
            class="cell"
            :class="{ departed: entry.departed }"
            :data-nearest="entry.departure.key === nearest ? '' : undefined"
        >
            <button type="button" class="entry" :aria-label="describe(entry)">
                <timetable-entry :entry="entry">
                    <template #line>
                        <line-symbol :line="entry.departure.line" simple class="symbol" />
                    </template>
                </timetable-entry>
                <ui-tooltip :triggers="['click']">
                    {{ entry.departure.display }}
                </ui-tooltip>
            </button>
        </li>
    </ol>
</template>

<script lang="ts" setup>
import type { TimetableListEntry } from "@/timetable";
import { formatTime } from "@/time";
import LineSymbol from "@/components/line/LineSymbol.vue";
import TimetableEntry from "@/components/timetable/TimetableEntry.vue";
import UiTooltip from "@/components/ui/UiTooltip.vue";

defineOptions({ name: "TimetableListCombined" });

defineProps<{
    /** The day's entries of all lines in departure order (`buildTimetableList`) */
    entries: TimetableListEntry[];
    /** The key of the run to scroll to, marked with `data-nearest` */
    nearest?: string;
}>();

// a cell shows the time and the line; its headsign is in the tooltip on tap, and here for screen readers
const describe = (entry: TimetableListEntry) =>
    `${formatTime(entry.planned)}, linia ${entry.departure.line.symbol}, kierunek ${entry.departure.display}`;
</script>

<style scoped>
@reference "@styles/tailwind.css";

/* dense on purpose: as many entries in a row as fit */
.cells {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(--spacing(20), 1fr));
    /* rows as tall as their cells, not all as tall as the tallest (the global reset's tracks) */
    grid-auto-rows: auto;
    column-gap: --spacing(1);
    margin: --spacing(3) 0 0;
    padding: 0;
    /* hairline */
    border-block-start: 1px solid var(--color-divider);
    list-style: none;
}

.cell {
    /* hairline */
    border-block-end: 1px solid var(--color-divider);

    &.departed {
        opacity: var(--opacity-muted);
    }
}

.entry {
    @apply focus-ring;
    display: block;
    inline-size: 100%;
    min-block-size: var(--control-height-lg);
    padding: --spacing(1) --spacing(2);
    border-radius: var(--radius-sm);
    color: inherit;
    font: inherit;
    text-align: start;

    &:hover {
        background-color: var(--color-hover);
    }
}

.symbol {
    font-size: var(--text-xs);
}
</style>
