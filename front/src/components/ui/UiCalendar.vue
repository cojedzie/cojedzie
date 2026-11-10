<template>
    <div ref="root" class="calendar" :class="[variant, flat && 'flat']">
        <div class="header">
            <button
                type="button"
                class="nav"
                aria-label="Poprzedni miesiąc"
                :disabled="!canMoveMonth(-1)"
                @click="moveMonth(-1)"
            >
                <ui-icon icon="chevron-left" />
            </button>
            <div :id="titleId" class="month" aria-live="polite">{{ formatMonth(month.month) }}</div>
            <button
                type="button"
                class="nav"
                aria-label="Następny miesiąc"
                :disabled="!canMoveMonth(1)"
                @click="moveMonth(1)"
            >
                <ui-icon icon="chevron-right" />
            </button>
        </div>
        <table class="weeks" role="grid" :aria-labelledby="titleId" @keydown="handleKeydown">
            <thead>
                <tr>
                    <th v-for="name in weekdays" :key="name.long" scope="col" :abbr="name.long">
                        {{ name.short }}
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="week in month.weeks" :key="week[0].date.toString()">
                    <td v-for="day in week" :key="day.date.toString()" :aria-selected="day.selected ? 'true' : 'false'">
                        <button
                            type="button"
                            class="day"
                            :class="{ outside: !day.inMonth, today: day.today, selected: day.selected }"
                            :data-date="day.date.toString()"
                            :tabindex="day.date.equals(focused) ? 0 : -1"
                            :disabled="day.disabled"
                            :aria-current="day.today ? 'date' : undefined"
                            :aria-label="formatLongDate(day.date)"
                            @click="pick(day.date)"
                        >
                            {{ day.date.day }}
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script lang="ts" setup>
import { computed, nextTick, shallowRef, useId, useTemplateRef, watch } from "vue";
import { buildMonth, clampDate, formatLongDate, formatMonth, Temporal, weekdayNames } from "@/time";
import { useToday } from "@/composables/useToday";
import UiIcon from "@/components/ui/UiIcon.vue";

defineOptions({ name: "UiCalendar" });

const props = withDefaults(
    defineProps<{
        /** Earliest day that can be chosen */
        min?: Temporal.PlainDate;
        /** Latest day that can be chosen */
        max?: Temporal.PlainDate;
        /** Overrides today as the app sees it (`useToday`) */
        today?: Temporal.PlainDate;
        /** `primary` fills the chosen day with the blue gradient, `normal` with black */
        variant?: "normal" | "primary";
        /** Without its own outline, inside something that frames it already (a popup, a filter panel) */
        flat?: boolean;
    }>(),
    { min: undefined, max: undefined, today: undefined, variant: "normal", flat: false },
);

/** The chosen day */
const modelValue = defineModel<Temporal.PlainDate>();

const appToday = useToday();
const today = computed(() => props.today ?? appToday.value);

const weekdays = weekdayNames();
const titleId = useId();
const root = useTemplateRef<HTMLElement>("root");

// the day that has the keyboard focus, it decides the month shown
const focused = shallowRef<Temporal.PlainDate>();
watch(
    [modelValue, () => props.min, () => props.max],
    () => (focused.value = clampDate(modelValue.value ?? today.value, props.min, props.max)),
    { immediate: true },
);

const month = computed(() =>
    buildMonth(focused.value.toPlainYearMonth(), {
        selected: modelValue.value,
        today: today.value,
        min: props.min,
        max: props.max,
    }),
);

function pick(date: Temporal.PlainDate) {
    focused.value = date;
    modelValue.value = date;
}

/** Whether the month `months` away has a day in range */
function canMoveMonth(months: -1 | 1): boolean {
    const end = months < 0 ? props.min : props.max;

    return (
        !end || Temporal.PlainYearMonth.compare(month.value.month.add({ months }), end.toPlainYearMonth()) * months <= 0
    );
}

function moveMonth(months: number) {
    focused.value = clampDate(focused.value.add({ months }), props.min, props.max);
}

const moves: Record<string, (date: Temporal.PlainDate, event: KeyboardEvent) => Temporal.PlainDate> = {
    ArrowLeft: date => date.subtract({ days: 1 }),
    ArrowRight: date => date.add({ days: 1 }),
    ArrowUp: date => date.subtract({ weeks: 1 }),
    ArrowDown: date => date.add({ weeks: 1 }),
    Home: date => date.subtract({ days: date.dayOfWeek - 1 }),
    End: date => date.add({ days: 7 - date.dayOfWeek }),
    PageUp: (date, event) => date.subtract(event.shiftKey ? { years: 1 } : { months: 1 }),
    PageDown: (date, event) => date.add(event.shiftKey ? { years: 1 } : { months: 1 }),
};

function handleKeydown(event: KeyboardEvent) {
    const move = moves[event.key];

    if (!move) {
        return;
    }

    event.preventDefault();
    focused.value = clampDate(move(focused.value, event), props.min, props.max);
    focus();
}

/** Moves the keyboard focus to the focused day, e.g. when the calendar opens. */
async function focus() {
    await nextTick();
    root.value?.querySelector<HTMLButtonElement>(`[data-date="${focused.value.toString()}"]`)?.focus();
}

defineExpose({ focus });
</script>

<style scoped>
@reference "@styles/tailwind.css";

.calendar {
    --calendar-fill: var(--color-dark);
    --calendar-accent: var(--color-dark);

    display: inline-flex;
    flex-direction: column;
    gap: --spacing(2);
    padding: --spacing(3);
    /* hairline */
    border: 1px solid var(--calendar-accent);
    border-radius: var(--radius-lg);
    background-color: white;
    font-size: var(--text-sm);
    line-height: var(--leading-tight);

    &.primary {
        --calendar-fill: var(--gradient-primary);
        --calendar-accent: var(--color-primary);
    }

    &.flat {
        padding: 0;
        border: 0;
        border-radius: 0;
        background: none;
    }

    & .header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: --spacing(2);
    }

    & .month {
        font-weight: bold;
        /* "październik 2026" without the month name breaking */
        white-space: nowrap;
    }

    & :is(.nav, .day) {
        @apply focus-ring;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-inline-size: var(--control-height-lg);
        min-block-size: var(--control-height-lg);
        padding: 0;
        border: 0;
        border-radius: var(--radius-md);
        background: none;
        color: inherit;
        font: inherit;
        cursor: pointer;

        &:hover:not(:disabled) {
            background-color: var(--color-hover);
        }

        &:disabled {
            opacity: var(--opacity-disabled);
            cursor: default;
        }
    }

    & .weeks {
        border-collapse: collapse;

        & th {
            padding-block-end: --spacing(1);
            color: var(--color-muted);
            font-size: var(--text-xs);
            font-weight: normal;
            text-align: center;
        }

        & td {
            /* hairline gap between the days */
            padding: 1px;
        }
    }

    & .day {
        inline-size: 100%;
        font-variant-numeric: tabular-nums;

        &.outside {
            color: var(--color-muted);
        }

        &.today {
            color: var(--calendar-accent);
            font-weight: bold;
            /* hairline */
            box-shadow: inset 0 0 0 1px currentColor;
        }

        &.selected {
            &,
            &:hover:not(:disabled) {
                background: var(--calendar-fill);
                color: white;
                font-weight: bold;
            }
        }
    }
}
</style>
