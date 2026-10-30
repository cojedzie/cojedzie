<template>
    <div>
        <div class="flex">
            <div class="flex relative" style="min-width: 0; flex: 1 1 auto">
                <slot name="primary-action" />
                <div class="overflow-hidden self-center">
                    <stop-label :stop="stop" />
                    <div v-if="destinations && destinations.length > 0" class="stop__destinations">
                        <ul>
                            <li
                                v-for="destination in destinations"
                                :key="destination.stop.id"
                                class="stop__destination destination"
                            >
                                <ul class="destination__lines">
                                    <li v-for="line in destination.lines" :key="line.id">
                                        <line-symbol :key="line.symbol" :line="line" simple />
                                    </li>
                                </ul>
                                <span class="destination__name ml-1">{{ destination.stop.name }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="stop__actions">
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
                class="ui-popup--no-padding"
                style="width: 500px"
                placement="right-start"
            >
                <stop-map :stop="stop" style="height: 300px" />
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
