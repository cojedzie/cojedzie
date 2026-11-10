<template>
    <div class="trip" :class="type">
        <ol ref="stops" v-dragscroll.x="!isTouch" class="stops">
            <li
                v-for="stop in stops"
                :key="stop.order"
                class="stop"
                :class="{ current: stop.current, visited: stop.visited }"
            >
                <div class="marker" />
                <div class="description">
                    <stop-label :stop="stop.stop" class="name" />
                </div>
                <div class="departure">
                    {{ formatClockTime(stop.departure, timeZone) }}
                </div>
            </li>
        </ol>
    </div>
</template>

<script lang="ts" setup>
import { computed, onMounted, PropType, useTemplateRef } from "vue";
import { TimetableEntry } from "@/model/trip";
import { LineType, Stop } from "@/model";
import { formatClockTime, Temporal } from "@/time";
import { useDisplayTimeZone } from "@/composables/useDisplayTimeZone";
import StopLabel from "@/components/stop/StopLabel.vue";
import { useIsTouch } from "@/composables/useIsTouch";

const isTouch = useIsTouch();
const timeZone = useDisplayTimeZone();

type TimetableEntryInfo = TimetableEntry & { visited: boolean; current: boolean };

defineOptions({ name: "TripTimetable" });

const props = defineProps({
    timetable: {
        type: Array as PropType<TimetableEntry[]>,
        required: true,
    },
    current: {
        type: Object as PropType<Stop>,
        required: true,
    },
    /** Colours the line as one of this type */
    type: {
        type: String as PropType<LineType>,
        default: undefined,
    },
});

const list = useTemplateRef<HTMLOListElement>("stops");

const stops = computed<TimetableEntryInfo[]>(() =>
    props.timetable.map(stop => ({
        ...stop,
        current: stop.stop.id == props.current.id,
        visited: Temporal.Instant.compare(Temporal.Now.instant(), stop.departure) > 0,
    })),
);

const scrollToCurrent = () => {
    const current = list.value?.querySelector<HTMLLIElement>(".stop.current");

    if (!list.value || !current) return;

    list.value.scrollLeft = current.offsetLeft - (list.value.clientWidth + current.clientWidth) / 2;
};

onMounted(scrollToCurrent);
</script>

<style scoped>
@reference "@styles/tailwind.css";

.trip {
    /* the stops' names slant up and right, this far */
    --name-slant: 60deg;
    --name-width: 250px;
    /* the stop marker's own size */
    --marker: 0.9rem;
    --marker-spacing: --spacing(3);
    --line-width: 2px;
    --line-color: var(--color-dark);
    --visited: color-mix(in srgb, var(--color-dark) 30%, transparent);

    display: flex;
    justify-content: center;

    &.trolleybus {
        --line-color: var(--color-line-trolleybus);
    }

    &.tram {
        --line-color: var(--color-line-tram);
    }

    &.bus {
        --line-color: var(--color-line-bus);
    }

    &.train {
        --line-color: var(--color-line-train);
    }

    &.unknown {
        --line-color: var(--color-line-unknown);
    }

    & .stops {
        @apply no-scrollbars;
        display: flex;
        overflow-x: auto;
        /* room for the slanted names */
        padding-block-start: calc(sin(var(--name-slant)) * var(--name-width));
        padding-inline: 0 calc(cos(var(--name-slant)) * var(--name-width));
        list-style: none;
        cursor: grab;
        user-select: none;

        &:active {
            cursor: grabbing;
        }
    }

    & .stop {
        position: relative;
        display: flex;
        flex-direction: column;
        flex-shrink: 0;
        align-items: center;
        justify-content: center;
        inline-size: --spacing(10);
    }

    & .marker {
        inline-size: var(--marker);
        block-size: var(--marker);
        margin-block: var(--marker-spacing);
        border: var(--line-width) solid var(--line-color);
        border-radius: var(--radius-full);
        background: white;

        /* the line to the previous and the next stop */
        &::before,
        &::after {
            position: absolute;
            inset-block-start: calc(var(--marker-spacing) + var(--marker) / 2);
            display: block;
            inline-size: calc(50% - var(--marker) / 2);
            block-size: var(--line-width);
            background: var(--line-color);
            translate: 0 -50%;
            content: "";
        }

        &::before {
            inset-inline-start: 0;
        }

        &::after {
            inset-inline-end: 0;
        }
    }

    & .stop:first-child .marker::before,
    & .stop:last-child .marker::after {
        content: none;
    }

    & .stop.current .marker {
        box-shadow: 0 0 --spacing(2) var(--line-color);
    }

    & .stop.visited {
        & .marker {
            border-color: var(--visited);

            &::before,
            &::after {
                background: var(--visited);
            }
        }

        & :is(.description, .departure) {
            opacity: var(--opacity-muted);
        }
    }

    & .stop.visited + .stop:not(.visited) .marker::before {
        background: var(--visited);
    }

    & .description {
        position: absolute;
        inset-block-start: 0;
        inset-inline-start: 50%;
        display: flex;
        max-inline-size: var(--name-width);
        /* rotated first, then moved along the slant: the order transform keeps and rotate/translate don't */
        transform: rotate(calc(var(--name-slant) * -1)) translateX(--spacing(3));
        transform-origin: 0 50%;
    }

    & .name {
        inline-size: max-content;
    }

    & .departure {
        margin-block-end: --spacing(2);
        font-size: var(--text-xs);
        font-weight: bold;
    }
}
</style>
