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
                        @mouseover="hoveredStop = destination.stop"
                        @mouseleave="hoveredStop = null"
                    >
                        <ui-icon icon="destination" class="mr-2" />
                        <stop-label :stop="destination.stop" class="stop-details-dialog__destination-name" />
                        <div class="stop-details-dialog__destination-lines">
                            <line-symbol v-for="line in destination.lines" :key="line.id" :line="line" />
                        </div>
                    </li>
                </ul>
            </div>
            <ui-map
                ref="map"
                :zoom="17"
                :center="stop.location"
                class="stop-details-modal__map"
                style="min-height: 450px"
            >
                <l-feature-group ref="features">
                    <l-marker :lat-lng="stop.location" />
                    <l-marker v-if="hoveredStop" :lat-lng="hoveredStop.location" />
                </l-feature-group>
            </ui-map>
        </div>
    </ui-dialog>
</template>

<script lang="ts">
import { computed, defineComponent, PropType, ref, watch } from "vue";
import { HasDestinations, Line, Stop } from "@/model";
import { LFeatureGroup } from '@vue-leaflet/vue-leaflet';
import useDataFromEndpoint from "@/composables/useDataFromEndpoint";
import { point } from "leaflet";
import { groupBy } from "@/utils";

export default defineComponent({
    name: "StopDetailsDialog",
    components: { LFeatureGroup },
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
            () => hoveredStop.value && tracksByDestination.value?.[hoveredStop.value.id]
        )

        const hoveredStop = ref<Stop & HasDestinations>(null);

        watch(bounds, async bounds => {
            const zoom = bounds && map.value?.leafletObject?.getBoundsZoom
                ? Math.min(map.value?.leafletObject.getBoundsZoom(bounds) - 0.3, 17)
                : 17;
            const center = bounds.getCenter() || props.stop.location;

            map.value?.leafletObject?.setView?.(center, zoom, { padding: point(100, 100) });
            map.value?.leafletObject?.invalidateSize();
        })

        return { lines, status, hoveredStop, features, map, bounds, tracksForDestination }
    }
})
</script>
