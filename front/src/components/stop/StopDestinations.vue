<template>
    <ul class="stop-destinations" :class="{ wrapping: wrap }">
        <li v-for="destination in compacted" :key="destination.name" class="destination">
            <ul class="lines">
                <li v-for="line in destination.lines" :key="line.type">
                    <line-symbol :line="line" simple />
                </li>
            </ul>
            <span class="name">{{ destination.name }}</span>
        </li>
    </ul>
</template>

<script lang="ts" setup>
import { computed } from "vue";
import type { Destination, Line } from "@/model";
import LineSymbol from "@/components/line/LineSymbol.vue";

defineOptions({ name: "StopDestinations" });

const props = defineProps<{
    /** Where the stop's lines go, each with the lines going there */
    destinations: Destination[];
    /** Wraps onto more rows instead of cutting the list at the end of one */
    wrap?: boolean;
}>();

// a few symbols say where the stop's lines go: stops of one name are one direction, and its lines of one type one
// symbol ("6, 8", "110…" for more than two)
const compacted = computed(() => {
    const byName = new Map<string, Line[]>();
    for (const { stop, lines } of props.destinations) {
        byName.set(stop.name, [...(byName.get(stop.name) ?? []), ...(lines ?? [])]);
    }

    return [...byName.entries()].map(([name, lines]) => ({ name, lines: compactLines(lines) }));
});

function compactLines(lines: Line[]): Line[] {
    const byType = new Map<string, Line[]>();
    for (const line of lines) {
        if (!byType.get(line.type)?.some(known => known.id === line.id)) {
            byType.set(line.type, [...(byType.get(line.type) ?? []), line]);
        }
    }

    return [...byType.values()].map(lines => ({
        ...lines[0],
        symbol: joinedSymbol(lines),
        night: lines.every(line => line.night),
        fast: lines.every(line => line.fast),
    }));
}

function joinedSymbol(lines: Line[]): string {
    if (lines.length > 2) {
        return `${lines[0].symbol}…`;
    }

    return lines.map(line => line.symbol).join(", ");
}
</script>

<style scoped>
@reference "@styles/tailwind.css";

.stop-destinations {
    @apply stop-list-inline;
    max-inline-size: 100%;

    &.wrapping {
        white-space: normal;
    }
}

.destination {
    white-space: nowrap;
}

.lines {
    display: inline;
    padding: 0;

    & > li {
        display: inline-block;

        &:not(:first-child) {
            margin-inline-start: 0.25em;
        }
    }
}

.name {
    margin-inline-start: --spacing(1);
}
</style>
