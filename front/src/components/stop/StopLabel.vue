<template>
    <div class="stop-label">
        <span class="name">{{ stop.name }}</span>
        <span v-if="stop.variant" class="badge variant">{{ stop.variant }}</span>
    </div>
</template>

<script lang="ts" setup>
import { PropType } from "vue";
import { Stop } from "@/model";

defineOptions({ name: "StopLabel" });

defineProps({
    stop: {
        type: Object as PropType<Stop>,
        required: true,
    },
});
</script>

<style scoped>
@reference "@styles/tailwind.css";

.stop-label {
    flex-wrap: nowrap;

    & .name {
        line-height: 1.1;
    }

    & .variant {
        margin-inline-start: 0.5em;
        /* hairline */
        border: 1px solid color-mix(in srgb, var(--color-dark) 75%, white);
        color: var(--color-dark);
    }
}
</style>
