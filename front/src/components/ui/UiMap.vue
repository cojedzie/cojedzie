<template>
    <l-map
        v-bind="{ ...$attrs, ...$props }"
        ref="map"
        :options="{ zoomSnap: 0.1, ...options }"
        :max-zoom="17"
    >
        <l-vector-layer
            :url="`https://api.maptiler.com/maps/bright/style.json?key=${ key }`"
            attribution="<a href=&quot;https://www.maptiler.com/copyright/&quot; target=&quot;_blank&quot;>© MapTiler</a> <a href=&quot;https://www.openstreetmap.org/copyright&quot; target=&quot;_blank&quot;>© OpenStreetMap contributors</a>"
        />

        <slot />
    </l-map>
</template>

<script lang="ts">
import { LMap, LVectorLayer } from "@/components/map";
import { computed, ComputedRef, defineComponent, onBeforeUnmount, onMounted, ref } from "vue";
import { Map } from "leaflet";
import { useAppConfig } from "@/composables/useAppConfig";

const leaflet = Symbol("leaflet")

const observer = new ResizeObserver(entries => {
    for (const entry of entries) {
        if (!entry.target[leaflet]) {
            return;
        }

        if (entry.contentRect.width === 0 || entry.contentRect.height === 0) {
            return;
        }

        const map = entry.target[leaflet] as ComputedRef<Map>;

        map?.value?.invalidateSize?.()
    }
})

export default defineComponent({
    name: "UiMap",
    components: {
        LMap,
        LVectorLayer
    },
    props: LMap.props,
    emits: LMap.emits,
    setup(props, { expose }) {
        const { maptiler: { key } } = useAppConfig();

        const map = ref();
        const leafletObject = computed<Map>(() => map.value?.leafletObject);

        expose({ leafletObject });

        onMounted(() => {
            observer.observe(map.value.root, { box: "border-box" });
            map.value.root[leaflet] = leafletObject;
        })

        onBeforeUnmount(() => {
            observer.unobserve(map.value.root);
            // prevent memory leak
            delete map.value.root[leaflet];
        })

        return {
            key,
            map
        }
    }
})
</script>
