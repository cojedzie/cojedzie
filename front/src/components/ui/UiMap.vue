<template>
    <l-map
        v-bind="{ ...$attrs, ...$props }"
        ref="map"
        :options="{ zoomSnap: 0.1, ...options }"
        :max-zoom="17"
        :padding="[ 10000, 10000 ]"
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
import { computed, defineComponent, ref } from "vue";
import { Map } from "leaflet";
import { useAppConfig } from "@/composables/useAppConfig";

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

        return {
            key,
            map
        }
    }
})
</script>
