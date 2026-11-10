<template>
    <span class="line-symbol" :class="[line.type, { night: line.night, fast: line.fast }]">
        <span class="parts">
            <slot v-if="!simple" name="icon">
                <span class="type-icon">
                    <ui-icon :icon="`line-${line.type}`" fixed-width />
                </span>
            </slot>
            <slot name="badge">
                <span class="badge badge-dark number">
                    <ui-icon v-if="line.night && !simple" icon="night" fixed-width class="night-icon" />
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

<style scoped>
@reference "@styles/tailwind.css";

.line-symbol {
    /* the line type's colour, and text readable on it */
    --line-color: currentColor;
    --line-ink: white;

    display: flex;
    align-items: center;

    &.trolleybus {
        --line-color: var(--color-line-trolleybus);
    }

    &.tram {
        --line-color: var(--color-line-tram);
    }

    &.bus {
        --line-color: var(--color-line-bus);
    }

    &.train {
        --line-color: var(--color-line-train);
        --line-ink: var(--color-body);
    }

    &.unknown {
        --line-color: var(--color-line-unknown);
    }

    & .parts {
        display: flex;
        align-items: stretch;
    }

    & .type-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        /* the icon's box, joined to the number */
        inline-size: 1.75rem;
        block-size: --spacing(6);
        padding: 0;
        background-color: var(--line-color);
        color: var(--line-ink);
    }

    & .number {
        display: flex;
        align-items: center;
        /* hairline */
        border: 1px solid var(--line-color);
        background-color: transparent;
        color: var(--line-color);
    }

    &.night .number {
        border: none;
        background-color: var(--color-dark);
        color: white;
    }

    & .night-icon {
        margin-inline-end: --spacing(1);
    }
}
</style>
