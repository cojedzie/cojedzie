<template>
    <ol class="rail" :style="{ '--line-color': `var(--color-line-${track.line.type})` }">
        <li v-for="(stop, index) in stops" :key="`${index}-${stop.id}`" class="stop">
            <span class="dot" aria-hidden="true" />
            <router-link
                v-if="index < stops.length - 1"
                :to="stopRoute(stop)"
                class="link stretched-link"
                :aria-current="stop.id === current ? 'page' : undefined"
            >
                <stop-label :stop="stop" />
            </router-link>
            <template v-else> <stop-label :stop="stop" /> <span class="terminus">przystanek końcowy</span> </template>
        </li>
    </ol>
</template>

<script lang="ts" setup>
import { computed } from "vue";
import type { RouteLocationRaw } from "vue-router";
import type { Stop, Track } from "@/model";
import StopLabel from "@/components/stop/StopLabel.vue";

// a track's stops in order on a line in the line's colour, each but the last opening the line's timetable there
defineOptions({ name: "TrackStopRail" });

const props = defineProps<{
    /** With its stops */
    track: Track;
    /** The open stop's id, marked as the current page */
    current?: string;
}>();

const stops = computed(() => props.track.stops ?? []);

/** The line's timetable at the stop, keeping the track and starting at today */
function stopRoute(stop: Stop): RouteLocationRaw {
    return {
        name: "timetable-line-stop",
        params: { line: props.track.line.id, stop: stop.id },
        query: { track: props.track.id },
    };
}
</script>

<style scoped>
@reference "@styles/tailwind.css";

.rail {
    /* the dot's own size, a one-off of the drawing */
    --dot: 0.75rem;
    /* a hairline, as on a trip's timetable */
    --line-width: 2px;
    --gutter: --spacing(3);

    margin: 0;
    padding: 0;
    list-style: none;
}

.stop {
    position: relative;
    display: flex;
    align-items: center;
    gap: --spacing(3);
    min-block-size: --spacing(10);
    padding-inline: var(--gutter) --spacing(2);
    border-radius: var(--radius-md);

    /* the line through the row, from the previous stop to the next one */
    &::before {
        position: absolute;
        inset-block: 0;
        inset-inline-start: calc(var(--gutter) + var(--dot) / 2);
        inline-size: var(--line-width);
        background: var(--line-color);
        translate: -50% 0;
        content: "";
    }

    &:first-child::before {
        inset-block-start: 50%;
    }

    &:last-child::before {
        inset-block-end: 50%;
    }

    &:has(.link):hover {
        background: var(--color-hover);
    }

    /* the open stop, as the search marks it */
    &:has([aria-current="page"]) {
        background: var(--color-hover-primary);
    }
}

.dot {
    position: relative;
    flex-shrink: 0;
    inline-size: var(--dot);
    block-size: var(--dot);
    border: var(--line-width) solid var(--line-color);
    border-radius: var(--radius-full);
    background: var(--color-light);
}

/* the route's ends are filled */
.stop:first-child .dot,
.stop:last-child .dot {
    background: var(--line-color);
}

.link {
    @apply focus-ring;
    color: inherit;
    text-decoration: none;
}

.terminus {
    margin-inline-start: auto;
    color: var(--color-muted);
    font-size: var(--text-xs);
}
</style>
