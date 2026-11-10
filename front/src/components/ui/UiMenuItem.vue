<template>
    <li>
        <component :is="tag" :type="tag === 'button' ? 'button' : undefined" class="item" v-bind="$attrs">
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

<style scoped>
@reference "@styles/tailwind.css";

.item {
    display: flex;
    align-items: center;
    gap: --spacing(2);
    inline-size: 100%;
    padding: --spacing(2) --spacing(3);
    color: var(--color-body);
    font-size: var(--text-xs);
    font-weight: bold;
    text-align: start;
    text-decoration: none;
    cursor: pointer;

    /* the current one of a list, e.g. the provider shown */
    &[aria-current="true"] {
        color: var(--color-primary);
    }

    /* beats the global a:hover */
    &:is(:hover, :focus-visible) {
        color: var(--color-body);
        text-decoration: none;
        background: var(--color-light);
    }
}
</style>
