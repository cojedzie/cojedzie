<template>
    <component
        :is="definition.icon"
        v-if="definition.type === 'simple'"
        v-bind="crop"
        class="ui-icon"
        :class="{
            'ui-icon--spin': spin || definition.spin,
            'ui-icon--fixed-width': fixedWidth || definition.fixedWidth,
        }"
    />
    <span
        v-else-if="definition.type === 'stacked'"
        class="ui-icon ui-icon--stacked"
        :class="{ 'ui-icon--spin': spin, 'ui-icon--fixed-width': fixedWidth }"
    >
        <component
            :is="layer.icon"
            v-for="(layer, index) in definition.icons"
            :key="index"
            v-bind="crop"
            :style="transform(layer)"
        />
    </span>
</template>

<script lang="ts" setup>
import { computed } from "vue";
import type { Component, PropType } from "vue";
import { icons } from "@/icons";
import type { Icon, IconLayer, PredefinedIcon } from "@/icons";

defineOptions({ name: "UiIcon" });

const props = defineProps({
    icon: {
        type: [String, Object, Function] as PropType<PredefinedIcon | Component>,
        validator: (value: PredefinedIcon | Component) =>
            typeof value !== "string" || Object.keys(icons).includes(value),
        required: true,
    },
    fixedWidth: Boolean,
    spin: Boolean,
});

const definition = computed<Icon>(() =>
    typeof props.icon === "string"
        ? icons[props.icon] || icons["unknown"]
        : { icon: props.icon as Component, type: "simple" },
);

// Tabler draws on a 24px grid with 2px of padding on each side, so its drawing would be smaller than the text around
// it. Cropping the padding off the view box makes it fill the icon while staying a plain, crisp svg. Our own icons
// drawn on the same grid take it too, the older ones on a 512px canvas ignore it (see custom() in icons.ts).
const crop = { viewBox: "2 2 20 20" };

const transform = ({ scale = 1, x = 0, y = 0 }: IconLayer) => ({
    transform: `translate(${x}em, ${y}em) scale(${scale})`,
});
</script>

<style lang="scss">
.ui-icon {
    display: inline-block;
    width: 1em;
    height: 1em;
    overflow: visible;
    vertical-align: -0.125em;
}

.ui-icon--fixed-width {
    width: 1.25em;
}

.ui-icon--spin {
    animation: ui-icon-spin 1s linear infinite;
}

.ui-icon--stacked {
    position: relative;

    > svg {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
    }
}

@keyframes ui-icon-spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}
</style>
