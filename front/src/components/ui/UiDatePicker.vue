<template>
    <div
        ref="root"
        class="ui-date-picker"
        :class="[`ui-date-picker--${variant}`, `ui-date-picker--${size}`]"
        role="group"
        :aria-label="label"
    >
        <button
            type="button"
            class="ui-date-picker__step"
            aria-label="Poprzedni dzień"
            :disabled="!canStep(-1)"
            @click="step(-1)"
        >
            <ui-icon icon="chevron-left" />
        </button>
        <button
            ref="field"
            type="button"
            class="ui-date-picker__field"
            aria-haspopup="dialog"
            :aria-expanded="open ? 'true' : 'false'"
            :aria-label="`Wybierz dzień, wybrany: ${formatLongDate(modelValue)}`"
            @click="open ? close() : show()"
        >
            <ui-icon icon="calendar" />
            <!--
                all labels share one cell: the hidden ones of the other days of the week keep the field as wide as the
                widest day name, so the arrows stay in place while clicking through days, and the long label can tell
                when it is cut
            -->
            <span ref="labels" class="ui-date-picker__labels" aria-hidden="true">
                <span v-for="sizer in sizers" :key="sizer" class="ui-date-picker__sizer">{{ sizer }}</span>
                <span ref="long" class="ui-date-picker__label" :class="{ 'ui-date-picker__label--hidden': short }">{{
                    formatLongDate(modelValue)
                }}</span>
                <span class="ui-date-picker__label" :class="{ 'ui-date-picker__label--hidden': !short }">{{
                    formatShortDate(modelValue)
                }}</span>
            </span>
        </button>
        <button
            type="button"
            class="ui-date-picker__step"
            aria-label="Następny dzień"
            :disabled="!canStep(1)"
            @click="step(1)"
        >
            <ui-icon icon="chevron-right" />
        </button>
        <template v-if="todayButton">
            <!-- keeps "dziś" apart from the arrow next to it, so it isn't hit by mistake -->
            <span class="ui-date-picker__separator" aria-hidden="true" />
            <!-- always there, so the control doesn't change its width when it leaves today -->
            <button type="button" class="ui-date-picker__today" :disabled="!canGoToday" @click="modelValue = today">
                dziś
            </button>
        </template>
        <teleport to="#popups">
            <ui-dialog
                v-if="open"
                :reference="root"
                placement="bottom"
                arrow
                class="ui-date-picker__popup"
                @leave="handleLeave"
            >
                <div @keydown.esc.stop="close({ returnFocus: true })">
                    <ui-calendar
                        ref="calendar"
                        :model-value="modelValue"
                        :min="min"
                        :max="max"
                        :today="today"
                        :variant="variant"
                        flat
                        @update:model-value="pick"
                    />
                </div>
            </ui-dialog>
        </teleport>
    </div>
</template>

<script lang="ts" setup>
import { computed, nextTick, ref, useTemplateRef, watch } from "vue";
import { useResizeObserver } from "@vueuse/core";
import { formatLongDate, formatShortDate, isWithin, Temporal } from "@/time";
import { useToday } from "@/composables/useToday";
import UiCalendar from "@/components/ui/UiCalendar.vue";
import UiDialog from "@/components/ui/UiDialog.vue";
import UiIcon from "@/components/ui/UiIcon.vue";

defineOptions({ name: "UiDatePicker" });

const props = withDefaults(
    defineProps<{
        /** Earliest day that can be chosen */
        min?: Temporal.PlainDate;
        /** Latest day that can be chosen */
        max?: Temporal.PlainDate;
        /** Overrides today as the app sees it (`useToday`) */
        today?: Temporal.PlainDate;
        /** Names the control for screen readers */
        label?: string;
        /** `primary` is blue, `normal` black */
        variant?: "normal" | "primary";
        size?: "small" | "normal" | "large";
        /** Shows the "dziś" button going back to today */
        todayButton?: boolean;
    }>(),
    {
        min: undefined,
        max: undefined,
        today: undefined,
        label: "Dzień",
        variant: "normal",
        size: "normal",
        todayButton: true,
    },
);

/** The chosen day */
const modelValue = defineModel<Temporal.PlainDate>({ required: true });

const appToday = useToday();
const today = computed(() => props.today ?? appToday.value);
const canGoToday = computed(() => !modelValue.value.equals(today.value) && isWithin(today.value, props.min, props.max));

function canStep(days: -1 | 1): boolean {
    return isWithin(modelValue.value.add({ days }), props.min, props.max);
}

function step(days: -1 | 1) {
    modelValue.value = modelValue.value.add({ days });
}

// the long labels of the chosen day's week, each day name once
const sizers = computed(() => {
    const monday = modelValue.value.subtract({ days: modelValue.value.dayOfWeek - 1 });

    return Array.from({ length: 7 }, (_, index) => formatLongDate(monday.add({ days: index })));
});

const root = useTemplateRef<HTMLElement>("root");
const field = useTemplateRef<HTMLButtonElement>("field");
const calendar = useTemplateRef<InstanceType<typeof UiCalendar>>("calendar");

const open = ref(false);

async function show() {
    open.value = true;
    await nextTick();
    calendar.value?.focus();
}

function close({ returnFocus = false } = {}) {
    open.value = false;

    if (returnFocus) {
        field.value?.focus();
    }
}

function pick(date: Temporal.PlainDate) {
    modelValue.value = date;
    close({ returnFocus: true });
}

function handleLeave(event?: Event) {
    // a click on the field toggles the calendar itself
    if (!field.value?.contains(event?.target as Node)) {
        close();
    }
}

// the long label ("poniedziałek, 09.11.2026") gives way to the short one ("pon. 09.11") when it doesn't fit
const labels = useTemplateRef<HTMLElement>("labels");
const long = useTemplateRef<HTMLElement>("long");
const short = ref(false);

function measure() {
    short.value = !!long.value && long.value.scrollWidth > long.value.clientWidth;
}

useResizeObserver(labels, measure);
watch(modelValue, () => nextTick(measure));
</script>

<style lang="scss">
@import "@styles/variables";

.ui-date-picker {
    @include ui-control;

    align-items: stretch;

    &__step,
    &__field,
    &__today {
        @include ui-control-segment;
    }

    &__step {
        flex: none;
        padding-inline: 0;
    }

    &__field {
        flex: 0 1 auto;
        min-inline-size: 0;
    }

    &__labels {
        display: grid;
        grid-template-columns: minmax(0, auto);
        min-inline-size: 0;
    }

    &__label,
    &__sizer {
        grid-area: 1 / 1;
        // digits of equal width, so only the day name changes the label's width
        font-variant-numeric: tabular-nums;
        text-align: center;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;

        &--hidden {
            visibility: hidden;
        }
    }

    &__sizer {
        visibility: hidden;
    }

    &__separator {
        flex: none;
        // shorter than the segments, a hint of a divide rather than a wall
        margin-block: calc(var(--ui-control-height) / 6);
        margin-inline: 0.25rem;
        inline-size: 1px;
        background-color: var(--ui-control-color);
        opacity: 0.5;
    }

    &__today {
        flex: none;
    }
}
</style>
