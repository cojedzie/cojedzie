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

<style lang="scss">
@import "../../../styles/_variables.scss";

$ui-help-width: 300px !default;
$ui-help-spacing: 0.5rem !default;
$ui-help-radius: 3px !default;

.help {
    box-sizing: content-box;
    width: $ui-help-width;
    font-size: 0.8rem;

    &__image {
        display: block;
        width: 100%;
        aspect-ratio: 2 / 1;
        margin-block: $ui-help-spacing * 0.5 $ui-help-spacing * 0.25;
        border-radius: $ui-help-radius;
        box-shadow: 0 0 1px black;
    }

    &__figure {
        margin-bottom: $ui-help-spacing;

        figcaption {
            color: $text-muted;
        }
    }
}
</style>
