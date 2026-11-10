<template>
    <section ref="root" class="stop-timetable" aria-label="Rozkład jazdy">
        <div class="controls">
            <ui-date-picker v-model="date" v-bind="picker" class="picker" />
            <div class="switches">
                <ui-toggle-group v-model="view" :options="views" label="Widok" v-bind="switches" />
                <ui-toggle-group
                    v-if="!lineFixed"
                    v-model="grouping"
                    :options="groupings"
                    label="Grupowanie"
                    v-bind="switches"
                />
            </div>
        </div>

        <p v-if="loading" class="empty">Wczytywanie rozkładu…</p>
        <p v-else-if="departures.length === 0" class="empty">Brak odjazdów w wybranym dniu</p>
        <timetable-list-per-line v-else-if="shown.kind === 'list-per-line'" :lists="shown.lists" :nearest="nearest" />
        <timetable-list-combined
            v-else-if="shown.kind === 'list-combined'"
            :entries="shown.entries"
            :nearest="nearest"
        />
        <timetable-hours-per-line
            v-else-if="shown.kind === 'hours-per-line'"
            :tables="shown.tables"
            :nearest="nearest"
        />
        <timetable-hours-combined v-else :rows="shown.rows" :nearest="nearest" />
    </section>
</template>

<script lang="ts" setup>
import { computed, nextTick, useTemplateRef, watch } from "vue";
import { useMediaQuery } from "@vueuse/core";
import type { Departure, TimetableRange } from "@/model";
import {
    buildCombinedTimetable,
    buildLineLists,
    buildLineTimetables,
    buildTimetableList,
    nearestEntry,
} from "@/timetable";
import { Temporal } from "@/time";
import type { UiToggleGroupOption } from "@/components/ui/UiToggleGroup.vue";
import TimetableHoursCombined from "@/components/timetable/TimetableHoursCombined.vue";
import TimetableHoursPerLine from "@/components/timetable/TimetableHoursPerLine.vue";
import TimetableListCombined from "@/components/timetable/TimetableListCombined.vue";
import TimetableListPerLine from "@/components/timetable/TimetableListPerLine.vue";
import UiDatePicker from "@/components/ui/UiDatePicker.vue";
import UiToggleGroup from "@/components/ui/UiToggleGroup.vue";

export type StopTimetableView = "list" | "hours";
export type StopTimetableGrouping = "line" | "combined";

defineOptions({ name: "StopTimetable" });

const props = defineProps<{
    /** The day's entries at the stop (`fetchTimetableDay`) */
    departures: Departure[];
    /** The provider's time zone, the timetable's hours are in it */
    timeZone: string;
    /** The days the timetable has, the date picker's limits */
    range?: TimetableRange;
    /** The stop's timetable of one line: the grouping is moot, and its switch hidden */
    lineFixed?: boolean;
    /** The day is being fetched: the controls stay, the views wait */
    loading?: boolean;
    /** The moment the day is seen at, only when it is today: the views mark the first run not gone yet, scrolled to */
    now?: Temporal.Instant;
}>();

/** The day shown */
const date = defineModel<Temporal.PlainDate>("date", { required: true });
const view = defineModel<StopTimetableView>("view", { default: "list" });
const grouping = defineModel<StopTimetableGrouping>("grouping", { default: "line" });

// the date is the page's main control, in the primary colour, the switches only change how it is shown; on a phone the
// switches are a step smaller than the date, so that both fit in one row under it
const desktop = useMediaQuery("(min-width: 768px)");
const picker = computed(() => ({ variant: "primary", min: props.range?.from, max: props.range?.to }) as const);
const switches = computed(() => ({ variant: "normal", size: desktop.value ? "normal" : "small" }) as const);

const views: UiToggleGroupOption<StopTimetableView>[] = [
    { value: "list", label: "Lista", icon: "timetable-list" },
    { value: "hours", label: "Godziny", icon: "timetable-hours" },
];
const groupings: UiToggleGroupOption<StopTimetableGrouping>[] = [
    { value: "line", label: "Linie" },
    { value: "combined", label: "Razem" },
];

// with one line both groupings look the same: its own table reads better
const perLine = computed(() => props.lineFixed || grouping.value === "line");

// the key of the first run not gone yet; the views mark it with `data-nearest`
const nearest = computed(() => {
    if (!props.now) {
        return undefined;
    }

    const sorted = [...props.departures].sort((a, b) => Temporal.Instant.compare(a.scheduled, b.scheduled));
    return nearestEntry(
        sorted.map(departure => ({ departure })),
        props.now,
    )?.departure.key;
});

// only the view shown is built
const shown = computed(() => {
    const { departures, timeZone } = props;

    if (view.value === "list") {
        return perLine.value
            ? { kind: "list-per-line" as const, lists: buildLineLists(departures, date.value, timeZone) }
            : { kind: "list-combined" as const, entries: buildTimetableList(departures, date.value, timeZone) };
    }

    return perLine.value
        ? { kind: "hours-per-line" as const, tables: buildLineTimetables(departures, date.value, timeZone) }
        : { kind: "hours-combined" as const, rows: buildCombinedTimetable(departures, date.value, timeZone) };
});

// a view shown anew (the day loaded, another view picked) starts at the current time; a minute passing doesn't scroll
const root = useTemplateRef<HTMLElement>("root");
watch(
    shown,
    async () => {
        await nextTick();
        root.value?.querySelector("[data-nearest]")?.scrollIntoView({ block: "center" });
    },
    { immediate: true },
);
</script>

<style scoped>
@reference "@styles/tailwind.css";

.controls {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: --spacing(2) --spacing(6);
    margin-block-start: --spacing(2);
}

.switches {
    display: flex;
    gap: --spacing(2);
}

/* on a phone the date takes the whole row, and the switches the row under it, from edge to edge */
@variant max-md {
    .picker,
    .switches {
        inline-size: 100%;
    }

    .switches {
        justify-content: space-between;
    }
}

.empty {
    margin-block-start: --spacing(4);
    color: var(--color-muted);
}
</style>
