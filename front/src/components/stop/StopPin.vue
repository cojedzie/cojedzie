<template>
    <ui-map-pin :lat-lng="stop.location" variant="filled-outline" :class="[`ui-pin--${type}`]">
        <slot v-if="!noLabel" name="sign">
            <div class="stop-sign" :class="[`stop-sign--type-${type}`]">
                <slot name="label">
                    <stop-label :stop="stop" />
                </slot>
            </div>
        </slot>
        <slot name="icon">
            <ui-icon icon="stop" />
        </slot>
    </ui-map-pin>
</template>

<script lang="ts">
import { defineComponent, PropType } from "vue";
import UiMapPin from "@/components/ui/UiMapPin.vue";
import StopLabel from "@/components/stop/StopLabel.vue";
import { getStopType, LineType, Stop } from "@/model";

const StopPin = defineComponent({
    name: "StopPin",
    components: {
        UiMapPin,
        StopLabel,
    },
    props: {
        stop: {
            type: Object as PropType<Stop>,
            required: true,
        },
        type: {
            type: String as PropType<LineType>,
            required: false,
            default({ stop }) {
                return getStopType(stop);
            },
        },
        noLabel: {
            type: Boolean,
            required: false,
        },
    },
});

export default StopPin;
</script>
