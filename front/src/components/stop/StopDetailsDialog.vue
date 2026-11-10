<template>
    <ui-dialog behaviour="modal" size="huge" flush v-bind="$attrs">
        <template #header="{ handleCloseClick }">
            <button class="close" @click="handleCloseClick">
                <ui-icon icon="close" />
            </button>
        </template>
        <div class="body">
            <div class="details">
                <div role="heading" class="header">
                    <stop-label :stop="stop" />
                </div>
                <div class="lines">
                    <line-symbol v-for="line in lines" :key="line.id" :line="line" />
                </div>
                <h3 class="heading">Kierunki</h3>
                <ul class="destinations">
                    <li
                        v-for="possibleDestination in stop.destinations"
                        :key="possibleDestination.stop.id"
                        class="destination"
                    >
                        <ui-icon icon="destination" class="destination-icon" />
                        <stop-label :stop="possibleDestination.stop" class="destination-name" />
                        <div class="lines destination-lines">
                            <line-symbol v-for="line in possibleDestination.lines" :key="line.id" :line="line" />
                        </div>
                        <div class="tools">
                            <button
                                class="btn btn-action pick"
                                :class="{ picked: destination?.id == possibleDestination.stop.id }"
                                @click="destination = possibleDestination.stop"
                            >
                                <ui-icon
                                    :icon="
                                        destination?.id == possibleDestination.stop.id
                                            ? 'map-marked:selected'
                                            : 'map-marked'
                                    "
                                />
                            </button>
                        </div>
                    </li>
                </ul>
            </div>
            <ui-map ref="map" :zoom="17" class="map">
                <l-feature-group ref="features">
                    <stop-pin :stop="stop" :type="selectedTrack?.line.type ?? type" />

                    <template v-if="selectedTrack">
                        <stop-pin
                            v-for="(stopOfSelectedTrack, index) in stopsToDestination"
                            :key="stopOfSelectedTrack.id"
                            :stop="stopOfSelectedTrack"
                            :type="selectedTrack.line.type"
                            variant="outline"
                            no-label
                        >
                            <template #icon>
                                {{ index + 1 }}
                            </template>
                        </stop-pin>
                    </template>

                    <stop-pin
                        v-if="destination"
                        :stop="destination"
                        variant="outline"
                        :type="selectedTrack?.line.type ?? type"
                    >
                        <template #icon>
                            <ui-icon icon="target" />
                        </template>
                    </stop-pin>
                </l-feature-group>

                <div v-if="destination" class="track-selector">
                    <track-picker v-model="selectedTrack" :tracks="tracksForDestination" placement="top-start" />
                </div>
            </ui-map>
        </div>
    </ui-dialog>
</template>

<script lang="ts" setup>
import { computed, defineAsyncComponent, PropType, ref, useTemplateRef, watch } from "vue";
import { getStopType, HasDestinations, Line, Stop, Track } from "@/model";
import { LFeatureGroup } from "@vue-leaflet/vue-leaflet";
import useDataFromEndpoint from "@/composables/useDataFromEndpoint";
import { Map, LatLngExpression, point, PointExpression } from "leaflet";
import StopPin from "@/components/stop/StopPin.vue";
import TrackRepository from "@/services/TrackRepository";
import { computedAsync } from "@vueuse/core";
import TrackPicker from "@/components/track/TrackPicker.vue";
import { slice } from "@/utils";
import useService from "@/composables/useService";
import UiDialog from "@/components/ui/UiDialog.vue";
import UiIcon from "@/components/ui/UiIcon.vue";
import StopLabel from "@/components/stop/StopLabel.vue";
import LineSymbol from "@/components/line/LineSymbol.vue";

const UiMap = defineAsyncComponent(() => import("@/components/ui/UiMap.vue"));

type OffsetOptions = {
    zoom?: number;
};

const offset = (map: Map, point: LatLngExpression, by: PointExpression, options: OffsetOptions = {}) => {
    return map.unproject(map.project(point, options.zoom).subtract(by), options.zoom);
};

defineOptions({
    name: "StopDetailsDialog",
    inheritAttrs: false,
});

const props = defineProps({
    stop: {
        type: Object as PropType<Stop & HasDestinations>,
        required: true,
    },
});

const { data: tracks } = useDataFromEndpoint("v1_stop_tracks", {
    params: { stop: props.stop.id as string },
    version: "1.0",
});

const trackRepository = useService(TrackRepository.service);

const features = useTemplateRef<InstanceType<typeof LFeatureGroup>>("features");
const map = useTemplateRef<{ leafletObject?: Map }>("map");

const bounds = computed(() => features.value?.leafletObject?.getBounds?.());

const lines = computed(() =>
    tracks.value
        ? Object.values(
              tracks.value
                  .map(t => t.track.line)
                  .reduce(
                      (lines, line: Line) => Object.assign(lines, { [line.symbol]: line }),
                      {} as Record<string, Line>,
                  ),
          )
        : [],
);

const selectedTrack = ref<Track>(null);
const destination = ref<Stop & HasDestinations>(null);

const tracksForDestination = computedAsync(
    async () =>
        destination.value && props.stop
            ? await trackRepository.getTracksForDestination(props.stop, destination.value)
            : null,
    null,
    { lazy: true },
);

const stopsToDestination = computed(() =>
    slice(
        selectedTrack.value?.stops ?? [],
        s => s.id == props.stop.id,
        s => s.id == destination.value.id,
    ).slice(1),
);

watch(destination, () => {
    selectedTrack.value = null;
});

watch(bounds, async bounds => {
    const leaflet = map.value?.leafletObject;

    if (!leaflet) {
        return;
    }

    const zoom =
        bounds && leaflet.getBoundsZoom
            ? Math.min(leaflet.getBoundsZoom(bounds, false, point([50, 100])) - 0.5, 17)
            : 17;

    const center: LatLngExpression = bounds?.getCenter() || props.stop.location;

    leaflet.setView?.(offset(leaflet, center, [0, 40], { zoom }), zoom);
});

const type = computed(() => getStopType(props.stop));
</script>

<style scoped>
@reference "@styles/tailwind.css";

/* the modal's padding is the dialog's own: the modal is flush */
.body {
    --pad: --spacing(8);

    display: grid;
    grid-template-columns: 1fr 1fr;
    min-block-size: 50vh;
    max-block-size: 70vh;

    @variant max-md {
        grid-template-rows: 250px 1fr;
        grid-template-columns: 1fr;
        /* the whole screen, less the backdrop's padding */
        block-size: calc(100dvh - --spacing(2));
        max-block-size: none;
        overflow-y: auto;
    }
}

.close {
    position: absolute;
    inset-block-start: 0;
    inset-inline-end: 0;
    z-index: var(--z-index-popup);
    padding: --spacing(2) --spacing(4);
    border-end-start-radius: var(--radius-sm);
    background: white;
    box-shadow: var(--shadow-popup);
}

.details {
    display: flex;
    flex-direction: column;
    grid-column: 1;
    padding: var(--pad);

    @variant max-md {
        grid-row: 2;
    }
}

.map {
    grid-column: 2;
    min-block-size: 60vh;

    @variant max-md {
        grid-row: 1;
        grid-column: 1;
        min-block-size: 0;
    }
}

.header {
    margin-block-end: --spacing(4);
    font-size: var(--text-2xl);
    font-weight: 600;
}

.heading {
    margin-block-start: --spacing(4);
    font-size: var(--text-lg);
    font-weight: 600;
}

.lines {
    display: flex;
    flex-wrap: wrap;
    gap: --spacing(1);
}

.destinations {
    overflow-y: auto;
    margin: 0 calc(var(--pad) * -1) calc(var(--pad) * -1);
    padding: 0;
    list-style: none;
}

.destination {
    display: grid;
    grid-template-columns: min-content auto min-content;
    grid-template-rows: 1fr 1fr;
    grid-template-areas:
        "icon name tools"
        "icon lines tools";
    gap: --spacing(1);
    padding: --spacing(2) var(--pad);

    &:nth-child(2n) {
        background: var(--color-hover);
    }
}

.destination-icon {
    grid-area: icon;
    place-self: center;
    margin-inline-end: --spacing(2);
}

.destination-name {
    display: flex;
    grid-area: name;
    align-items: center;
}

.destination-lines {
    grid-area: lines;
}

.tools {
    display: flex;
    grid-area: tools;
    align-items: center;
}

.pick {
    inline-size: --spacing(10);
    block-size: --spacing(10);
    padding: 0;
    border-radius: var(--radius-full);

    &:hover {
        background-color: var(--color-hover);
    }

    &.picked {
        color: var(--color-primary);
    }
}

.track-selector {
    @apply ui-popup;
    position: absolute;
    inset-block-end: --spacing(8);
    inset-inline: --spacing(8);
    padding: 0;
}
</style>
