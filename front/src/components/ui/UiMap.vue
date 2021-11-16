<template>
  <l-map v-bind="{ ...$attrs, ...$props }">
      <l-vector-layer url="https://api.maptiler.com/maps/bright/style.json?key=8GX5FRUNgk4lB83GZT8Q"
                      token="not-needed"
                      attribution='<a href="https://www.maptiler.com/copyright/" target="_blank">© MapTiler</a> <a href="https://www.openstreetmap.org/copyright" target="_blank">© OpenStreetMap contributors</a>'
      />

      <slot />
  </l-map>
</template>

<script lang="ts">
import { LControl, LIcon, LMap, LMarker, LPopup, LTileLayer, LVectorLayer } from "@/components/map";
import { defineComponent } from "vue";

export default defineComponent({
    name: "UiMap",
    components: {
        LControl,
        LIcon,
        LMap,
        LMarker,
        LPopup,
        LTileLayer,
        LVectorLayer
    },
    props: LMap.props,
    emits: LMap.emits,
})
</script>
