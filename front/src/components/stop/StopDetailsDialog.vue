<template>
    <ui-dialog behaviour="modal" class="ui-modal--huge ui-modal--no-padding" v-bind="$attrs">
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
                <h3 class="ui-modal__heading">
                    Kierunki
                </h3>
                <ul class="stop-details-dialog__destinations">
                    <li
                        v-for="destination in stop.destinations"
                        :key="destination.stop.id"
                        class="stop-details-dialog__destination"
                    >
                        <ui-icon icon="destination" class="mr-2" />
                        <stop-label :stop="destination.stop" class="stop-details-dialog__destination-name" />
                        <div class="stop-details-dialog__destination-lines">
                            <line-symbol v-for="line in destination.lines" :key="line.id" :line="line" />
                        </div>
                        <div class="stop-details-dialog__actions">
                            <button class="btn btn-action" @click="selectedStop = destination.stop">
                                <ui-icon icon="map-marked" />
                            </button>
                        </div>
                    </li>
                </ul>
            </div>
            <ui-map
                ref="map"
                :zoom="17"
                :center="stop.location"
                class="stop-details-modal__map"
            >
                <l-feature-group ref="features">
                    <stop-pin :stop="stop" />
                    <stop-pin v-if="selectedStop" :stop="selectedStop" variant="outline" :type="type">
                        <template #icon>
                            <ui-icon icon="target" />
                        </template>
                    </stop-pin>
                </l-feature-group>
            </ui-map>
        </div>
    </ui-dialog>
</template>

<script lang="ts">
import { computed, defineComponent, PropType, ref, watch } from "vue";
import { getStopType, HasDestinations, Line, Stop } from "@/model";
import { LFeatureGroup } from '@vue-leaflet/vue-leaflet';
import useDataFromEndpoint from "@/composables/useDataFromEndpoint";
import { Map, LatLngExpression, point, PointExpression } from "leaflet";
import { groupBy } from "@/utils";
import StopPin from "@/components/stop/StopPin.vue";

type OffsetOptions = {
    zoom?: number,
}

const offset = (map: Map, point: LatLngExpression, by: PointExpression, options: OffsetOptions = {}) => {
    return map.unproject(map.project(point, options.zoom).subtract(by), options.zoom)
}

export default defineComponent({
    name: "StopDetailsDialog",
    components: { StopPin, LFeatureGroup },
    inheritAttrs: false,
    props: {
        stop: {
            type: Object as PropType<Stop & HasDestinations>,
            required: true,
        }
    },
    setup(props) {
        const { data: tracks, status } = useDataFromEndpoint("v1_stop_tracks", {
            params: { stop: props.stop.id as string },
            version: "1.0",
        })

        const features = ref<LFeatureGroup>();
        const map = ref();

        const bounds = computed(() => features.value?.leafletObject?.getBounds?.());

        const lines = computed(
            () => tracks.value
                ? Object.values(
                    tracks.value
                        .map(t => t.track.line)
                        .reduce((lines, line: Line) => Object.assign(lines, { [line.symbol]: line }), {} as Record<string, Line>)
                )
                : []
        )

        const tracksByDestination = computed(
            () => groupBy(tracks.value || [], track => track.track.destination.id),
        );

        const tracksForDestination = computed(
            () => selectedStop.value && tracksByDestination.value?.[selectedStop.value.id]
        )

        const selectedStop = ref<Stop & HasDestinations>(null);

        watch(bounds, async bounds => {
            const leaflet: Map = map.value?.leafletObject;

            if (!leaflet) {
                return;
            }

            const zoom = bounds && leaflet.getBoundsZoom
                ? Math.min(leaflet.getBoundsZoom(bounds, false, point([ 50, 100 ])) - 0.5, 17)
                : 17;

            const center: LatLngExpression = bounds?.getCenter() || props.stop.location;

            leaflet.setView?.(offset(leaflet, center, [ 0, 40 ], { zoom }), zoom);
        })

        const type = computed(() => getStopType(props.stop));

        return { lines, status, selectedStop, features, map, bounds, tracksForDestination, type }
    }
})
</script>
