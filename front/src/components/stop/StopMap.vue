<template>
    <div>
        <ui-map :center="stop.location" :zoom="17" :options="{ zoomControl: false, dragging: false }">
            <stop-pin :stop="stop" variant="outline" />
        </ui-map>
    </div>
</template>

<script lang="ts">
import { getStopType, LineType, Stop } from "@/model";
import { defineComponent, PropType } from "vue";
import StopPin from "@/components/stop/StopPin.vue";

export default defineComponent({
    name: "StopMap",
    components: {
        StopPin,
    },
    props: {
        stop: {
            type: Object as PropType<Stop>,
            required: true,
        },
        type: {
            type: String as PropType<LineType>,
            required: false,
            default: ({ stop }) => getStopType(stop),
        }
    }
})
</script>
