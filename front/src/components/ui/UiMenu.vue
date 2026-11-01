<template>
    <ul class="ui-menu">
        <slot />
    </ul>
</template>

<script setup lang="ts">
defineOptions({ name: "UiMenu" });

defineSlots<{ default?: () => unknown }>();
</script>

<style lang="scss">
@import "../../../styles/_variables.scss";

.ui-menu {
    // the items' hover background reaches the edges of the popup
    margin: 0 (-$popup-padding);
    padding: 0;
    list-style: none;
}
</style>
