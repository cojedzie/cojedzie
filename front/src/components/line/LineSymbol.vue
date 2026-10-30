<template>
    <span
        class="line__symbol hstack"
        :class="{
            [`line--${line.type}`]: true,
            'line--night': line.night,
            'line--fast': line.fast,
        }"
    >
        <span class="hstack items-stretch">
            <slot v-if="!simple" name="icon">
                <span class="icon">
                    <ui-icon :icon="`line-${line.type}`" fixed-width />
                </span>
            </slot>
            <slot name="badge">
                <span class="badge badge-dark hstack">
                    <ui-icon v-if="line.night && !simple" icon="night" fixed-width />
                    {{ line.symbol }}
                    <ui-icon v-if="line.fast" icon="fast" />
                </span>
            </slot>
        </span>
    </span>
</template>

<script lang="ts" setup>
import { PropType } from "vue";
import { Line } from "@/model";
import UiIcon from "@/components/ui/UiIcon.vue";

defineOptions({ name: "LineSymbol" });

defineProps({
    line: {
        type: Object as PropType<Line>,
        required: true,
    },
    simple: Boolean,
});
</script>
