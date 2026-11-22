<template>
    <button
        ref="button"
        class="btn btn-action settings-button"
        :class="size"
        aria-haspopup="dialog"
        :aria-expanded="open ? 'true' : 'false'"
        @click="open = !open"
    >
        <ui-tooltip>{{ label }}</ui-tooltip>
        <ui-icon icon="settings" fixed-width />
    </button>
    <teleport to="#popups">
        <ui-dialog
            v-if="open"
            :reference="button"
            arrow
            :placement="placement"
            :aria-label="label"
            @leave="open = false"
            @keydown.esc="close"
        >
            <div ref="content">
                <slot />
            </div>
        </ui-dialog>
    </teleport>
</template>

<script lang="ts" setup>
import { PropType, ref, useTemplateRef } from "vue";
import { FloatingPlacement } from "@/composables/useFloating";
import { usePopupFocus } from "@/composables/usePopupFocus";
import UiDialog from "@/components/ui/UiDialog.vue";
import UiIcon from "@/components/ui/UiIcon.vue";
import UiTooltip from "@/components/ui/UiTooltip.vue";

defineOptions({ name: "UiSectionSettings" });

defineProps({
    label: { type: String, default: "ustawienia" },
    placement: { type: String as PropType<FloatingPlacement>, default: "left-start" },
    /** `large` is a 44px tap target on a phone and a control's height from md, for a gear outside a section's header */
    size: { type: String as PropType<"normal" | "large">, default: "normal" },
});

defineSlots<{ default?: () => unknown }>();

const button = useTemplateRef<HTMLButtonElement>("button");
const content = useTemplateRef<HTMLElement>("content");
const open = ref(false);

const close = usePopupFocus(
    open,
    () => content.value,
    () => button.value,
);
</script>

<style scoped>
@reference "@styles/tailwind.css";

.settings-button.large {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-inline-size: --spacing(11);
    min-block-size: --spacing(11);

    @variant md {
        min-inline-size: var(--control-height-lg);
        min-block-size: var(--control-height-lg);
    }
}
</style>
