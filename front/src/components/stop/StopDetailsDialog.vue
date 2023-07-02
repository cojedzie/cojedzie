<template>
    <ui-dialog behaviour="modal" class="ui-modal--huge ui-modal--no-padding stop-details-dialog" v-bind="$attrs">
        <template #header="{ handleCloseClick }">
            <button class="stop-details-dialog__close" @click="handleCloseClick">
                <ui-icon icon="close" />
            </button>
        </template>
        <div class="stop-details-dialog__body">
            <div class="stop-details-dialog__details">
                <div role="heading" class="stop-details-dialog__header">
                    <stop-label :stop="stop" />
                </div>
                <div class="stop-details-dialog__lines">
                    <line-symbol v-for="line in lines" :key="line.id" :line="line" />
                </div>
                <h3 class="ui-modal__heading">Kierunki</h3>
                <ul class="stop-details-dialog__destinations">
                    <li
                        v-for="possibleDestination in stop.destinations"
                        :key="possibleDestination.stop.id"
                        class="stop-details-dialog__destination"
                    >
                        <ui-icon icon="destination" class="mr-2" />
                        <stop-label :stop="possibleDestination.stop" class="stop-details-dialog__destination-name" />
                        <div class="stop-details-dialog__destination-lines">
                            <line-symbol v-for="line in possibleDestination.lines" :key="line.id" :line="line" />
                        </div>
                        <div class="stop-details-dialog__actions">
                            <button
                                class="btn btn-action"
                                :class="{
                                    'btn-toggled': destination?.id == possibleDestination.stop.id,
                                }"
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
            <ui-map ref="map" :zoom="17" class="stop-details-modal__map">
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

<script lang="ts">
import { computed, defineComponent, PropType, ref, watch } from "vue";
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

type OffsetOptions = {
    zoom?: number;
};

const offset = (map: Map, point: LatLngExpression, by: PointExpression, options: OffsetOptions = {}) => {
    return map.unproject(map.project(point, options.zoom).subtract(by), options.zoom);
};

export default defineComponent({
    name: "StopDetailsDialog",
    components: { TrackPicker, StopPin, LFeatureGroup },
    inheritAttrs: false,
    props: {
        stop: {
            type: Object as PropType<Stop & HasDestinations>,
            required: true,
        },
    },
    setup(props) {
        const { data: tracks, status } = useDataFromEndpoint("v1_stop_tracks", {
            params: { stop: props.stop.id as string },
            version: "1.0",
        });

        const trackRepository = useService(TrackRepository.service);

        const features = ref<LFeatureGroup>();
        const map = ref();

        const bounds = computed(() => features.value?.leafletObject?.getBounds?.());

        const lines = computed(() =>
            tracks.value
                ? Object.values(
                      tracks.value
                          .map(t => t.track.line)
                          .reduce(
                              (lines, line: Line) => Object.assign(lines, { [line.symbol]: line }),
                              {} as Record<string, Line>
                          )
                  )
                : []
        );

        const tracksForDestination = computedAsync(
            async () =>
                destination.value && props.stop
                    ? await trackRepository.getTracksForDestination(props.stop, destination.value)
                    : null,
            null,
            { lazy: true }
        );

        const selectedTrack = ref<Track>(null);
        const destination = ref<Stop & HasDestinations>(null);

        const stopsToDestination = computed(() =>
            slice(
                selectedTrack.value?.stops ?? [],
                s => s.id == props.stop.id,
                s => s.id == destination.value.id
            ).slice(1)
        );

        watch(destination, () => {
            selectedTrack.value = null;
        });

        watch(bounds, async bounds => {
            const leaflet: Map = map.value?.leafletObject;

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

        return {
            lines,
            status,
            destination,
            selectedTrack,
            stopsToDestination,
            features,
            map,
            bounds,
            tracksForDestination,
            type,
        };
    },
});
</script>
