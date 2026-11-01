<template>
    <li>
        <component :is="tag" :type="tag === 'button' ? 'button' : undefined" class="ui-menu__item" v-bind="$attrs">
            <slot />
        </component>
    </li>
</template>

<script setup lang="ts">
import type { Component } from "vue";

// attributes, listeners and classes go to the item itself, not to the <li> around it
defineOptions({ name: "UiMenuItem", inheritAttrs: false });

withDefaults(defineProps<{ tag?: string | Component }>(), { tag: "button" });

defineSlots<{ default?: () => unknown }>();
</script>

<style lang="scss">
@import "../../../styles/_variables.scss";

.ui-menu__item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    width: 100%;
    padding: 0.375rem $popup-padding;
    color: $body-color;
    font-size: 0.75rem;
    font-weight: 700;
    text-align: start;
    text-decoration: none;
    cursor: pointer;

    // beats the global a:hover
    &:hover,
    &:focus-visible {
        color: $body-color;
        text-decoration: none;
        background: $gray-100;
    }
}
</style>
