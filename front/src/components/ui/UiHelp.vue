<template>
    <slot name="button" v-bind="{ toggle, ref: el => (button = el), $attrs }">
        <button v-bind="$attrs" ref="button" type="button" @click="toggle">
            <ui-icon icon="info" fixed-width />
        </button>
    </slot>
    <teleport v-if="active" to="#popups">
        <ui-dialog
            :reference="button"
            :offset="{ mainAxis: 20, crossAxis: -12 }"
            class="help"
            placement="right-start"
            mobile-behaviour="modal"
        >
            <template #title>
                <slot name="title" />
            </template>
            <slot />
        </ui-dialog>
    </teleport>
</template>

<script lang="ts">
import { createMutex } from "@/composables/useMutex";
import UiDialog from "@/components/ui/UiDialog.vue";
import UiIcon from "@/components/ui/UiIcon.vue";

export type HelpTrigger = "click" | "long-hover";

const sharedMutex = createMutex();
</script>

<script lang="ts" setup>
import { PropType, ref } from "vue";
import { supply } from "@/utils";
import { Mutex, useMutex } from "@/composables/useMutex";

defineOptions({ name: "UiHelp" });

const props = defineProps({
    trigger: {
        type: Array as PropType<HelpTrigger[]>,
        required: false,
        default: supply<HelpTrigger[]>(["click", "long-hover"]),
    },
    mutex: {
        type: Object as PropType<Mutex>,
        required: false,
        default: sharedMutex,
    },
});

const button = ref<HTMLElement>(null);
const { active, toggle } = useMutex(props.mutex);
</script>

<style scoped>
@reference "@styles/tailwind.css";

.help {
    box-sizing: content-box;
    /* the help popup's own width, its figures are 2:1 within it */
    inline-size: 300px;
    font-size: var(--text-xs);

    /* figures come from the help's content: `help-figure` and `help-image` are the slot's contract */
    & :slotted(.help-image) {
        display: block;
        inline-size: 100%;
        aspect-ratio: 2 / 1;
        margin-block: --spacing(1);
        border-radius: var(--radius-sm);
        /* hairline frame */
        box-shadow: 0 0 1px black;
    }

    & :slotted(.help-figure) {
        margin-block-end: --spacing(2);

        & figcaption {
            color: var(--color-muted);
        }
    }
}
</style>
