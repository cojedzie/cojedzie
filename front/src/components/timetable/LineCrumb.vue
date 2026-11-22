<template>
    <span class="line-crumb">Linia <line-symbol :line="line" /></span>
</template>

<script setup lang="ts">
import type { Line } from "@/model";
import LineSymbol from "@/components/line/LineSymbol.vue";

// a line in the breadcrumbs: the word, so it reads as a place, and the symbol, as on the line chips
defineOptions({ name: "LineCrumb" });

defineProps<{ line: Line }>();
</script>

<style scoped>
@reference "@styles/tailwind.css";

.line-crumb {
    display: inline-flex;
    align-items: center;
    gap: --spacing(1.5);
}
</style>
