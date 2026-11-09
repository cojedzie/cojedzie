<template>
    <div
        ref="group"
        class="ui-toggle-group"
        :class="[`ui-toggle-group--${variant}`, `ui-toggle-group--${size}`, animated && 'ui-toggle-group--animated']"
        role="group"
        :aria-label="label"
    >
        <div v-if="thumb" class="ui-toggle-group__thumb" :style="thumb" aria-hidden="true" />
        <button
            v-for="option in options"
            ref="buttons"
            :key="String(option.value)"
            type="button"
            class="ui-toggle-group__option"
            :class="{ 'ui-toggle-group__option--selected': option.value === modelValue }"
            :aria-pressed="option.value === modelValue ? 'true' : 'false'"
            @click="modelValue = option.value"
        >
            <ui-icon v-if="option.icon" :icon="option.icon" />
            <span class="ui-toggle-group__label">{{ option.label }}</span>
        </button>
    </div>
</template>

<script lang="ts" setup generic="T">
import { nextTick, ref, useTemplateRef, watch } from "vue";
import { useResizeObserver } from "@vueuse/core";
import type { PredefinedIcon } from "@/icons";
import UiIcon from "@/components/ui/UiIcon.vue";

export type UiToggleGroupOption<T> = { value: T; label: string; icon?: PredefinedIcon };

defineOptions({ name: "UiToggleGroup" });

const props = withDefaults(
    defineProps<{
        options: UiToggleGroupOption<T>[];
        /** Names the choice for screen readers, e.g. "Widok" */
        label: string;
        /** `primary` fills the selected option with the blue gradient, `normal` with black */
        variant?: "normal" | "primary";
        size?: "small" | "normal" | "large";
    }>(),
    { variant: "normal", size: "normal" },
);

const modelValue = defineModel<T>({ required: true });

const group = useTemplateRef<HTMLElement>("group");
const buttons = useTemplateRef<HTMLButtonElement[]>("buttons");

// The thumb sits under the selected option and slides to the next one, like the switch's does. Options differ in
// width, so it takes the size and place of the selected button.
const thumb = ref<{ insetInlineStart: string; inlineSize: string }>();
// the thumb shows up in place at first, it slides only once it is there
const animated = ref(false);

function place() {
    const index = props.options.findIndex(option => option.value === modelValue.value);
    const button = buttons.value?.[index];

    thumb.value = button
        ? { insetInlineStart: `${button.offsetLeft}px`, inlineSize: `${button.offsetWidth}px` }
        : undefined;
}

watch([modelValue, () => props.options], () => nextTick(place));
useResizeObserver(group, () => {
    place();
    requestAnimationFrame(() => (animated.value = thumb.value !== undefined));
});
</script>

<style lang="scss">
@import "@styles/variables";

$ui-toggle-group-duration: 200ms;

.ui-toggle-group {
    @include ui-control;

    position: relative;
    isolation: isolate;

    &__thumb {
        position: absolute;
        z-index: -1;
        inset-block: 2px;
        // the group's padding is inside the button's offset already
        border-radius: calc(var(--ui-control-radius) - 2px);
        background: var(--ui-control-fill);
    }

    &--animated &__thumb {
        transition:
            inset-inline-start $ui-toggle-group-duration ease-in-out,
            inline-size $ui-toggle-group-duration ease-in-out;
    }

    &__option {
        @include ui-control-segment;

        flex: 1 1 auto;
        min-inline-size: 0;

        &--selected {
            color: $white;

            &:hover:not(:disabled) {
                background-color: transparent;
            }
        }
    }

    // until the thumb is in place, the selected option is filled itself
    &:not(&--animated) &__option--selected {
        &,
        &:hover:not(:disabled) {
            background: var(--ui-control-fill);
        }
    }

    &__label {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    @media (prefers-reduced-motion: reduce) {
        &__thumb {
            transition: none !important;
        }
    }
}
</style>
