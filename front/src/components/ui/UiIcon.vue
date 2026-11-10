<template>
    <component
        :is="definition.icon"
        v-if="definition.type === 'simple'"
        v-bind="crop"
        class="ui-icon"
        :class="{ spin: spin || definition.spin, 'fixed-width': fixedWidth || definition.fixedWidth }"
    />
    <span
        v-else-if="definition.type === 'stacked'"
        class="ui-icon stacked"
        :class="{ spin, 'fixed-width': fixedWidth }"
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
// are drawn on the same grid and take it too.
const crop = { viewBox: "2 2 20 20" };

const transform = ({ scale = 1, x = 0, y = 0 }: IconLayer) => ({
    transform: `translate(${x}em, ${y}em) scale(${scale})`,
});
</script>

<style scoped>
/* `ui-icon`, not a short name: global stylesheets not yet moved into their components still target icons by it */
.ui-icon {
    display: inline-block;
    /* icons are sized by the text around them */
    inline-size: 1em;
    block-size: 1em;
    overflow: visible;
    vertical-align: -0.125em;

    &.fixed-width {
        inline-size: 1.25em;
    }

    &.spin {
        animation: ui-icon-spin 1s linear infinite;
    }

    &.stacked {
        position: relative;

        & > svg {
            position: absolute;
            inset: 0;
            inline-size: 100%;
            block-size: 100%;
        }
    }
}

@keyframes ui-icon-spin {
    to {
        rotate: 1turn;
    }
}
</style>
