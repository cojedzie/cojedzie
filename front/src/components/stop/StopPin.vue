<template>
    <ui-map-pin
        :lat-lng="stop.location"
        variant="filled-outline"
        :style="{ '--ui-pin-color': `var(--color-line-${type})` }"
    >
        <slot v-if="!noLabel" name="sign">
            <div class="sign" :class="type">
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

<script lang="ts" setup>
import { PropType } from "vue";
import UiMapPin from "@/components/ui/UiMapPin.vue";
import StopLabel from "@/components/stop/StopLabel.vue";
import { getStopType, LineType, Stop } from "@/model";
import UiIcon from "@/components/ui/UiIcon.vue";

defineOptions({ name: "StopPin" });

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
    noLabel: {
        type: Boolean,
        required: false,
    },
});
</script>

<style scoped>
@reference "@styles/tailwind.css";

/* the stop's name above its pin */
.sign {
    --sign-color: currentColor;

    position: absolute;
    inset-block-start: calc(--spacing(3) * -1);
    inset-inline-start: 50%;
    padding: --spacing(1) --spacing(2);
    /* hairline */
    border: 1px solid var(--sign-color);
    border-radius: var(--radius-sm);
    background: white;
    color: black;
    font-size: var(--text-xs);
    font-weight: 600;
    white-space: nowrap;
    translate: -50% -100%;

    &.trolleybus {
        --sign-color: var(--color-line-trolleybus);
    }

    &.tram {
        --sign-color: var(--color-line-tram);
    }

    &.bus {
        --sign-color: var(--color-line-bus);
    }

    &.train {
        --sign-color: var(--color-line-train);
    }

    &.unknown {
        --sign-color: var(--color-line-unknown);
    }
}
</style>
