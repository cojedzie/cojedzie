<template>
    <button ref="button" class="btn btn-action" :aria-expanded="open ? 'true' : 'false'" @click="open = !open">
        <ui-tooltip>{{ label }}</ui-tooltip>
        <ui-icon icon="settings" fixed-width />
    </button>
    <teleport to="#popups">
        <ui-dialog v-if="open" :reference="button" arrow :placement="placement" @leave="open = false">
            <slot />
        </ui-dialog>
    </teleport>
</template>

<script lang="ts" setup>
import { PropType, ref, useTemplateRef } from "vue";
import { FloatingPlacement } from "@/composables/useFloating";
import UiDialog from "@/components/ui/UiDialog.vue";
import UiIcon from "@/components/ui/UiIcon.vue";
import UiTooltip from "@/components/ui/UiTooltip.vue";

defineOptions({ name: "UiSectionSettings" });

defineProps({
    label: { type: String, default: "ustawienia" },
    placement: { type: String as PropType<FloatingPlacement>, default: "left-start" },
});

defineSlots<{ default?: () => unknown }>();

const button = useTemplateRef<HTMLButtonElement>("button");
const open = ref(false);
</script>
