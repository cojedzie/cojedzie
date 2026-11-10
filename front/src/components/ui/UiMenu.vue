<template>
    <ul class="menu">
        <slot />
    </ul>
</template>

<script setup lang="ts">
defineOptions({ name: "UiMenu" });

defineSlots<{ default?: () => unknown }>();
</script>

<style scoped>
@reference "@styles/tailwind.css";

.menu {
    /* the items' hover background reaches the edges of the popup, past its padding */
    margin: 0 calc(--spacing(3) * -1);
    padding: 0;
    list-style: none;
}
</style>
