<template>
    <div class="tables">
        <section v-for="table in tables" :key="table.line.id" class="line">
            <timetable-line-heading :line="table.line">{{ table.headsign }}</timetable-line-heading>
            <div v-for="row in table.rows" :key="row.hour" class="row">
                <span class="hour">{{ pad(row.hour) }}</span>
                <span class="minutes">
                    <span
                        v-for="(minute, index) in row.minutes"
                        :key="index"
                        class="minute"
                        :class="{ departed: minute.departed }"
                    >
                        <span>{{ pad(minute.minute) }}</span>
                        <sup v-if="minute.note" class="note">{{ minute.note }}</sup>
                    </span>
                </span>
            </div>
            <timetable-legend v-if="table.legend.length > 0" :legend="table.legend" />
        </section>
    </div>
</template>

<script lang="ts" setup>
import type { LineTimetable } from "@/timetable";
import TimetableLegend from "@/components/timetable/TimetableLegend.vue";
import TimetableLineHeading from "@/components/timetable/TimetableLineHeading.vue";

defineOptions({ name: "TimetableHoursPerLine" });

defineProps<{
    /** The day's entries, one table per line in rows of hours (`buildLineTimetables`) */
    tables: LineTimetable[];
}>();

// hours and minutes as a printed timetable writes them: "05", "07"
const pad = (value: number) => value.toString().padStart(2, "0");
</script>

<style scoped>
@reference "@styles/tailwind.css";

/* like printed stop timetables: on a wide screen the lines' tables stand side by side */
.tables {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(min(100%, --spacing(72)), 1fr));
    grid-auto-rows: auto;
    column-gap: --spacing(6);
}

.line {
    min-inline-size: 0;
    margin-block-start: --spacing(5);
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
    font-weight: bold;
    font-variant-numeric: tabular-nums;
}

.minutes {
    display: flex;
    flex: 1;
    flex-wrap: wrap;
    gap: --spacing(1) --spacing(3);
    min-inline-size: 0;
}

.minute {
    display: inline-flex;
    align-items: baseline;
    font-variant-numeric: tabular-nums;

    &.departed {
        opacity: var(--opacity-muted);
    }
}

.note {
    /* hairline: the letter sits against the minute */
    margin-inline-start: 1px;
    font-size: var(--text-xs);
    font-weight: bold;
}
</style>
