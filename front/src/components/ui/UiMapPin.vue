<template>
    <l-marker v-bind="markerProps">
        <l-icon :icon-anchor="[16, 48.67]">
            <ui-pin v-bind="{ ...uiPinProps, ...$attrs }">
                <slot />
            </ui-pin>
        </l-icon>
    </l-marker>
</template>

<script lang="ts" setup>
import UiPin, { UiPinVariant } from "@/components/ui/UiPin.vue";
import { LIcon, LMarker } from "@vue-leaflet/vue-leaflet";
import { computed, PropType } from "vue";
import { pick } from "lodash";

defineOptions({
    name: "UiMapPin",
    inheritAttrs: false,
});

const props = defineProps({
    variant: {
        type: String as PropType<UiPinVariant>,
        default: "outline" as UiPinVariant,
    },
    ...LMarker.props,
});

const uiPinProps = computed(() => pick(props, ["variant"]));
const markerProps = computed(() => pick(props, Object.keys(LMarker.props)));
</script>
