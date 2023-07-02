<template>
    <l-marker v-bind="markerProps">
        <l-icon :icon-anchor="[16, 48.67]">
            <ui-pin v-bind="{ ...uiPinProps, ...$attrs }">
                <slot />
            </ui-pin>
        </l-icon>
    </l-marker>
</template>

<script lang="ts">
import UiPin, { UiPinProps } from "@/components/ui/UiPin.vue";
import { LIcon, LMarker } from "@vue-leaflet/vue-leaflet";
import { computed, defineComponent } from "vue";
import { pick } from "lodash";

export type UiMapProps = {
    variant: string;
};

export default defineComponent({
    name: "UiMapPin",
    components: {
        UiPin,
        LIcon,
    },
    inheritAttrs: false,
    props: {
        ...UiPinProps,
        ...LMarker.props,
    },
    setup(props) {
        const uiPinProps = computed(() => pick(props, Object.keys(UiPin.props)));
        const markerProps = computed(() => pick(props, Object.keys(LMarker.props)));

        return {
            uiPinProps,
            markerProps,
        };
    },
});
</script>
