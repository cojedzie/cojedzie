<template>
    <div class="stop-label" :class="{ truncate }">
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
    /** stays on one line, shortening the name to fit and keeping the code whole */
    truncate: Boolean,
});
</script>

<style scoped>
@reference "@styles/tailwind.css";

.stop-label {
    flex-wrap: nowrap;

    & .name {
        line-height: 1.1;
    }

    &.truncate {
        display: flex;
        align-items: center;
        min-inline-size: 0;
        white-space: nowrap;

        & .name {
            overflow: hidden;
            text-overflow: ellipsis;
        }

        & .variant {
            flex-shrink: 0;
        }
    }

    & .variant {
        margin-inline-start: 0.5em;
        /* hairline */
        border: 1px solid color-mix(in srgb, var(--color-dark) 75%, white);
        color: var(--color-dark);
    }
}
</style>
