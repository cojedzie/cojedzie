<template>
    <main ref="main" class="flex">
        <router-view />
    </main>
</template>

<script setup lang="ts">
import { onMounted, useTemplateRef } from "vue";

const main = useTemplateRef<HTMLElement>("main");

onMounted(() => {
    main.value.classList.remove("not-ready");
});
</script>
