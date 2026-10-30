<template>
    <div class="stop">
        <span class="stop__name">{{ stop.name }}</span>
        <span v-if="stop.variant" class="stop__variant">{{ stop.variant }}</span>
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
