<template>
    <div>
        <div class="row">
            <div class="main">
                <slot name="primary-action" />
                <div class="description">
                    <stop-label :stop="stop" />
                    <div v-if="destinations && destinations.length > 0" class="destinations">
                        <ul class="destination-list">
                            <li v-for="destination in destinations" :key="destination.stop.id" class="destination">
                                <ul class="destination-lines">
                                    <li v-for="line in destination.lines" :key="line.id">
                                        <line-symbol :key="line.symbol" :line="line" simple />
                                    </li>
                                </ul>
                                <span class="destination-name">{{ destination.stop.name }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="tools">
                <slot name="actions">
                    <button ref="action-info" class="btn btn-action" @click="details = !details">
                        <ui-tooltip>dodatkowe informacje</ui-tooltip>
                        <ui-icon icon="info" />
                    </button>

                    <button
                        ref="action-map"
                        v-hover="(hovered: boolean) => (mapButtonHovered = hovered)"
                        class="btn btn-action"
                    >
                        <ui-icon icon="map" />
                    </button>
                </slot>
            </div>
        </div>

        <keep-alive>
            <teleport to="#popups">
                <stop-details-dialog v-if="details" :stop="stop" @leave="details = false" />
            </teleport>
        </keep-alive>
        <keep-alive>
            <ui-dialog
                v-if="showMap"
                v-hover="(hovered: boolean) => (mapHovered = hovered)"
                reference="action-map"
                arrow
                flush
                class="map-popup"
                placement="right-start"
            >
                <stop-map :stop="stop" class="map" />
            </ui-dialog>
        </keep-alive>
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import type { Line, StopWithDestinations as Stop } from "@/model";
import { match, unique } from "@/utils";
import StopDetailsDialog from "@/components/stop/StopDetailsDialog.vue";
import UiDialog from "@/components/ui/UiDialog.vue";
import UiIcon from "@/components/ui/UiIcon.vue";
import UiTooltip from "@/components/ui/UiTooltip.vue";
import StopLabel from "@/components/stop/StopLabel.vue";
import StopMap from "@/components/stop/StopMap.vue";
import LineSymbol from "@/components/line/LineSymbol.vue";

defineOptions({ name: "StopPickerEntry" });

const props = defineProps<{ stop: Stop }>();

const details = ref(false);
const mapButtonHovered = ref(false);
const mapHovered = ref(false);

const showMap = computed(() => mapHovered.value || mapButtonHovered.value);

const groupLinesByType = (lines: Line[]) =>
    lines.reduce<{ [kind: string]: Line[] }>(
        (groups, line) => ({
            ...groups,
            [line.type]: [...(groups[line.type] || []), line],
        }),
        {},
    );

const joinedSymbol = match<string, [Line[]]>(
    [lines => lines.length === 1, lines => lines[0].symbol],
    [lines => lines.length === 2, ([first, second]) => `${first.symbol}, ${second.symbol}`],
    [lines => lines.length > 2, ([first]) => `${first.symbol}…`],
);

const compactLines = destination => ({
    ...destination,
    lines: Object.entries(groupLinesByType(destination.lines || [])).map(([type, lines]) => ({
        type: type,
        symbol: joinedSymbol(lines),
        night: lines.every(line => line.night),
        fast: lines.every(line => line.fast),
    })),
    all: destination.lines,
});

const destinations = computed(() =>
    unique(props.stop.destinations || [], destination => destination.stop && destination.stop.name).map(compactLines),
);
</script>

<style scoped>
@reference "@styles/tailwind.css";

.row {
    display: flex;
}

.main {
    position: relative;
    display: flex;
    flex: 1 1 auto;
    min-inline-size: 0;
}

.description {
    align-self: center;
    overflow: hidden;
}

.destinations {
    display: flex;
    align-items: center;
    font-size: var(--text-xs);
}

.destination-list {
    @apply stop-list-inline;
    max-inline-size: 100%;
}

.destination-lines {
    display: inline;
    padding: 0;

    & > li {
        display: inline-block;

        &:not(:first-child) {
            margin-inline-start: 0.25em;
        }
    }
}

.destination-name {
    margin-inline-start: --spacing(1);
}

.tools {
    display: flex;
}

/* the map popup's own size */
.map-popup {
    inline-size: 500px;
}

.map {
    block-size: 300px;
}
</style>
