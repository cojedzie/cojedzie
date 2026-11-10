<template>
    <div class="table">
        <div v-for="row in rows" :key="row.hour" class="row">
            <span class="hour">{{ pad(row.hour) }}</span>
            <span class="minutes">
                <button
                    v-for="(minute, index) in row.minutes"
                    :key="index"
                    type="button"
                    class="minute"
                    :class="{ departed: minute.departed }"
                    :aria-label="describe(row.hour, minute)"
                >
                    <span class="digits">{{ pad(minute.minute) }}</span>
                    <line-symbol :line="minute.line" simple class="symbol" />
                    <ui-tooltip :triggers="['click']">{{ minute.headsign }}</ui-tooltip>
                </button>
            </span>
        </div>
    </div>
</template>

<script lang="ts" setup>
import type { CombinedMinute, CombinedTimetable } from "@/timetable";
import LineSymbol from "@/components/line/LineSymbol.vue";
import UiTooltip from "@/components/ui/UiTooltip.vue";

defineOptions({ name: "TimetableHoursCombined" });

defineProps<{
    /** The day's entries of all lines, in rows of hours (`buildCombinedTimetable`) */
    rows: CombinedTimetable;
}>();

// hours and minutes as a printed timetable writes them: "05", "07"
const pad = (value: number) => value.toString().padStart(2, "0");

// a cell shows the minute and the line; its headsign is in the tooltip on tap, and here for screen readers
const describe = (hour: number, minute: CombinedMinute) =>
    `${pad(hour)}:${pad(minute.minute)}, linia ${minute.line.symbol}, kierunek ${minute.headsign}`;
</script>

<style scoped>
@reference "@styles/tailwind.css";

.table {
    margin-block-start: --spacing(3);
    /* hairline */
    border-block-start: 1px solid var(--color-divider);
}

.row {
    display: flex;
    gap: --spacing(2);
    padding-block: --spacing(1);
    /* hairline */
    border-block-end: 1px solid var(--color-divider);
}

.hour {
    flex-shrink: 0;
    inline-size: --spacing(8);
    padding-block-start: --spacing(2);
    font-weight: bold;
    font-variant-numeric: tabular-nums;
}

.minutes {
    display: flex;
    flex: 1;
    flex-wrap: wrap;
    /* hairline: the minutes sit close, their hover marks each */
    gap: 1px;
    min-inline-size: 0;
}

/* dense on purpose: the minute with its line under it */
.minute {
    @apply focus-ring;
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    /* hairlines: the minute and its line close together */
    gap: 1px;
    min-inline-size: --spacing(9);
    min-block-size: var(--control-height-lg);
    padding: 1px --spacing(1);
    border-radius: var(--radius-sm);
    color: inherit;
    font: inherit;

    &:hover {
        background-color: var(--color-hover);
    }

    &.departed {
        opacity: var(--opacity-muted);
    }
}

.digits {
    font-variant-numeric: tabular-nums;
    line-height: var(--leading-tight);
}

.symbol {
    font-size: var(--text-xs);
}
</style>
