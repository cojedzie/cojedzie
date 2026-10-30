<template>
    <div>
        <ui-map :center="stop.location" :zoom="17" :options="{ zoomControl: false, dragging: false }">
            <stop-pin :stop="stop" variant="outline" />
        </ui-map>
    </div>
</template>

<script lang="ts" setup>
import { defineAsyncComponent, PropType } from "vue";
import { getStopType, LineType, Stop } from "@/model";
import StopPin from "@/components/stop/StopPin.vue";

const UiMap = defineAsyncComponent(() => import("@/components/ui/UiMap.vue"));

defineOptions({ name: "StopMap" });

defineProps({
    stop: {
        type: Object as PropType<Stop>,
        required: true,
    },
    type: {
        type: String as PropType<LineType>,
        required: false,
        default: ({ stop }: { stop: Stop }) => getStopType(stop),
    },
});
</script>
