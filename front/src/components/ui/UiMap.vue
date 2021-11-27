<template>
    <l-map v-bind="{ ...$attrs, ...$props }">
        <l-vector-layer :url="`https://api.maptiler.com/maps/bright/style.json?key=${ key }`"
                        attribution='<a href="https://www.maptiler.com/copyright/" target="_blank">© MapTiler</a> <a href="https://www.openstreetmap.org/copyright" target="_blank">© OpenStreetMap contributors</a>'
        />

        <slot/>
    </l-map>
</template>

<script lang="ts">
import { LMap, LVectorLayer } from "@/components/map";
import { defineComponent } from "vue";
import { useAppConfig } from "@/utils/config";

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
