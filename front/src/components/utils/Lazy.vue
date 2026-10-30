<template>
    <keep-alive>
        <slot v-if="visible"></slot>
    </keep-alive>
</template>

<script lang="ts" setup>
import { ref, watch } from "vue";

// eslint-disable-next-line vue/multi-word-component-names
defineOptions({ name: "Lazy" });

const props = defineProps({
    activate: Boolean,
});

const visible = ref(false);

watch(
    () => props.activate,
    (value, old) => {
        visible.value = value || old;
    },
);
</script>
