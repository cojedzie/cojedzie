<template>
    <l-map v-bind="{ ...$attrs, ...$props }" ref="map" :options="mapOptions" :max-zoom="17">
        <l-vector-layer
            :url="`https://api.maptiler.com/maps/bright/style.json?key=${key}`"
            attribution='<a href="https://www.maptiler.com/copyright/" target="_blank">© MapTiler</a> <a href="https://www.openstreetmap.org/copyright" target="_blank">© OpenStreetMap contributors</a>'
        />

        <slot />
    </l-map>
</template>

<script lang="ts" setup>
import { LMap, LVectorLayer } from "@/components/map";
import { computed, ComputedRef, onBeforeUnmount, onMounted, ref } from "vue";
import { Map, MapOptions } from "leaflet";
import { useAppConfig } from "@/composables/useAppConfig";

const leaflet = Symbol("leaflet");

const observer = new ResizeObserver(entries => {
    for (const entry of entries) {
        if (!entry.target[leaflet]) {
            return;
        }

        if (entry.contentRect.width === 0 || entry.contentRect.height === 0) {
            return;
        }

        const map = entry.target[leaflet] as ComputedRef<Map>;

        map?.value?.invalidateSize?.();
    }
});

defineOptions({ name: "UiMap" });

const props = defineProps(LMap.props);
defineEmits(LMap.emits);

const {
    maptiler: { key },
} = useAppConfig();

const map = ref();

// LMap ships no typings, so its props are not known here
const mapOptions = computed<MapOptions>(() => ({
    zoomSnap: 0.1,
    ...(props as { options?: MapOptions }).options,
}));

const leafletObject = computed<Map>(() => map.value?.leafletObject);

defineExpose({ leafletObject });

onMounted(() => {
    observer.observe(map.value.root, { box: "border-box" });
    map.value.root[leaflet] = leafletObject;
});

onBeforeUnmount(() => {
    observer.unobserve(map.value.root);

    // prevent memory leak
    delete map.value.root[leaflet];
});
</script>
