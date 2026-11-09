<template>
    <div ref="root" class="ui-calendar" :class="[`ui-calendar--${variant}`, flat && 'ui-calendar--flat']">
        <div class="ui-calendar__header">
            <button
                type="button"
                class="ui-calendar__nav"
                aria-label="Poprzedni miesiąc"
                :disabled="!canMoveMonth(-1)"
                @click="moveMonth(-1)"
            >
                <ui-icon icon="chevron-left" />
            </button>
            <div :id="titleId" class="ui-calendar__month" aria-live="polite">{{ formatMonth(month.month) }}</div>
            <button
                type="button"
                class="ui-calendar__nav"
                aria-label="Następny miesiąc"
                :disabled="!canMoveMonth(1)"
                @click="moveMonth(1)"
            >
                <ui-icon icon="chevron-right" />
            </button>
        </div>
        <table class="ui-calendar__grid" role="grid" :aria-labelledby="titleId" @keydown="handleKeydown">
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
                            class="ui-calendar__day"
                            :class="{
                                'ui-calendar__day--outside': !day.inMonth,
                                'ui-calendar__day--today': day.today,
                                'ui-calendar__day--selected': day.selected,
                            }"
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

<style lang="scss">
@import "@styles/variables";

.ui-calendar {
    --ui-calendar-fill: #{$dark};
    --ui-calendar-accent: #{$dark};

    display: inline-flex;
    flex-direction: column;
    gap: 0.5rem;
    padding: 0.75rem;
    border: 1px solid var(--ui-calendar-accent);
    border-radius: 0.5rem;
    background-color: $white;
    font-size: $font-size-sm;
    line-height: 1.25;

    &--primary {
        --ui-calendar-fill: #{$primary-gradient};
        --ui-calendar-accent: #{$primary};
    }

    &--flat {
        padding: 0;
        border: 0;
        border-radius: 0;
        background: none;
    }

    &__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
    }

    &__month {
        font-weight: bold;
        // "październik 2026" without the month name breaking
        white-space: nowrap;
    }

    &__nav,
    &__day {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-inline-size: 2.25rem;
        min-block-size: 2.25rem;
        padding: 0;
        border: 0;
        border-radius: 0.375rem;
        background: none;
        color: inherit;
        font: inherit;
        cursor: pointer;

        &:hover:not(:disabled) {
            background-color: rgba($dark, 0.08);
        }

        &:focus-visible {
            outline: 2px solid rgba($primary, 0.5);
            outline-offset: 1px;
        }

        &:disabled {
            opacity: 0.3;
            cursor: default;
        }
    }

    &__grid {
        border-collapse: collapse;

        th {
            padding-block-end: 0.25rem;
            color: $text-muted;
            font-size: 0.75rem;
            font-weight: normal;
            text-align: center;
        }

        td {
            padding: 1px;
        }
    }

    &__day {
        inline-size: 100%;
        font-variant-numeric: tabular-nums;

        &--outside {
            color: $text-muted;
        }

        &--today {
            color: var(--ui-calendar-accent);
            font-weight: bold;
            box-shadow: inset 0 0 0 1px currentColor;
        }

        &--selected {
            &,
            &:hover:not(:disabled) {
                background: var(--ui-calendar-fill);
                color: $white;
                font-weight: bold;
            }
        }
    }
}
</style>
