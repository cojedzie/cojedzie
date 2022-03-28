<template>
    <l-map v-bind="{ ...$attrs, ...$props }">
        <l-vector-layer
            :url="`https://api.maptiler.com/maps/bright/style.json?key=${ key }`"
            attribution="<a href=&quot;https://www.maptiler.com/copyright/&quot; target=&quot;_blank&quot;>© MapTiler</a> <a href=&quot;https://www.openstreetmap.org/copyright&quot; target=&quot;_blank&quot;>© OpenStreetMap contributors</a>"
        />

        <slot />
    </l-map>
</template>

<script lang="ts">
import { LMap, LVectorLayer } from "@/components/map";
import { defineComponent } from "vue";
import { useAppConfig } from "@/composables/useAppConfig";

export default defineComponent({
    name: "UiMap",
    components: {
        LMap,
        LVectorLayer
    },
    props: LMap.props,
    emits: LMap.emits,
    setup() {
        const { maptiler: { key } } = useAppConfig();

        return {
            key
        }
    }
})
</script>
