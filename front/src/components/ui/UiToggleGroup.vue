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
    --ui-toggle-group-color: #{$dark};
    --ui-toggle-group-thumb: #{$dark};
    --ui-toggle-group-hover: #{rgba($dark, 0.08)};
    --ui-toggle-group-radius: 0.375rem;
    --ui-toggle-group-height: 2rem;
    --ui-toggle-group-padding: 0.75rem;
    --ui-toggle-group-font-size: #{$font-size-sm};

    position: relative;
    isolation: isolate;
    display: inline-flex;
    max-inline-size: 100%;
    padding: 2px;
    border: 1px solid var(--ui-toggle-group-color);
    border-radius: var(--ui-toggle-group-radius);
    background-color: $white;
    color: var(--ui-toggle-group-color);
    font-size: var(--ui-toggle-group-font-size);
    font-weight: bold;
    line-height: 1.25;

    &--primary {
        --ui-toggle-group-color: #{$primary};
        --ui-toggle-group-thumb: #{$primary-gradient};
        --ui-toggle-group-hover: #{$nav-hover-background};
    }

    &--small {
        --ui-toggle-group-radius: 0.25rem;
        --ui-toggle-group-height: 1.5rem;
        --ui-toggle-group-padding: 0.5rem;
        --ui-toggle-group-font-size: 0.75rem;
    }

    &--large {
        --ui-toggle-group-radius: 0.5rem;
        // a comfortable touch target, with the outline and padding it is 44px high
        --ui-toggle-group-height: 2.5rem;
        --ui-toggle-group-padding: 1rem;
    }

    &__thumb {
        position: absolute;
        z-index: -1;
        inset-block: 2px;
        // the group's padding is inside the button's offset already
        border-radius: calc(var(--ui-toggle-group-radius) - 2px);
        background: var(--ui-toggle-group-thumb);
    }

    // until the thumb is in place, the selected option is filled itself
    &:not(&--animated) &__option--selected {
        background: var(--ui-toggle-group-thumb);
    }

    &--animated &__thumb {
        transition:
            inset-inline-start $ui-toggle-group-duration ease-in-out,
            inline-size $ui-toggle-group-duration ease-in-out;
    }

    &__option {
        display: inline-flex;
        flex: 1 1 auto;
        min-inline-size: 0;
        align-items: center;
        justify-content: center;
        gap: 0.375rem;
        min-block-size: var(--ui-toggle-group-height);
        padding-inline: var(--ui-toggle-group-padding);
        border: 0;
        border-radius: calc(var(--ui-toggle-group-radius) - 2px);
        background: none;
        color: inherit;
        font: inherit;
        cursor: pointer;
        transition:
            color $ui-toggle-group-duration ease-in-out,
            background-color $ui-toggle-group-duration ease-in-out;

        &:hover:not(.ui-toggle-group__option--selected) {
            background-color: var(--ui-toggle-group-hover);
        }

        &:focus-visible {
            outline: 2px solid rgba($primary, 0.5);
            outline-offset: 1px;
        }

        &--selected {
            color: $white;
        }
    }

    &__label {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    @media (prefers-reduced-motion: reduce) {
        &__thumb,
        &__option {
            transition: none !important;
        }
    }
}
</style>
